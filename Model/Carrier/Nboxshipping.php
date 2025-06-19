<?php
declare(strict_types=1);

namespace Nbox\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Type;
use Nbox\Shipping\Helper\ProductSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Helper\ConfigHelper;
use Nbox\Shipping\Utils\Converter;
use Nbox\Shipping\Service\DataFormatter;

/**
 * Custom shipping method for Nbox to calculate shipping rates based on weight and dimensions.
 */
class Nboxshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var ProductSource
     */
    protected $productSource;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var NboxApi
     */
    protected $nboxApi;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var string
     */
    protected $_code = 'nboxshipping';

    /**
     * Nboxshipping constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param ProductRepository $productRepository
     * @param ConfigHelper $configHelper
     * @param NboxApi $nboxApi
     * @param Curl $curl
     * @param ProductSource $productSource
     * @param Converter $converter
     * @param DataFormatter $dataFormatter
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ProductRepository $productRepository,
        ConfigHelper $configHelper,
        NboxApi $nboxApi,
        Curl $curl,
        ProductSource $productSource,
        Converter $converter, // Added the Converter dependency
        DataFormatter $dataFormatter,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->productRepository = $productRepository;
        $this->configHelper = $configHelper;
        $this->nboxApi = $nboxApi;
        $this->_curl = $curl;
        $this->productSource = $productSource;
        $this->converter = $converter; // Set the converter instance
        $this->dataFormatter = $dataFormatter;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get allowed methods for this carrier.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['nboxshipping' => $this->getConfigData('name')];
    }

    /**
     * Collect rates for the shipment.
     *
     * @param RateRequest $request
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active') || !$this->configHelper->isPluginActive()) {
            $this->_logger->debug("Stopped Nbox Method");
            return false;
        }

        /**
         * Start custom script
         * Call Nbox Now Rates API here
         */

        $items = $request->getAllItems();
        
        // Use DataFormatter service to format addresses and products
        $origin = $this->dataFormatter->formatOriginAddress();
        $destination = $this->dataFormatter->formatDestinationFromRateRequest($request);
        $products = $this->dataFormatter->formatProductsFromQuoteItems($items);
        
        // Calculate totals from products
        $totalVolume = 0;
        $totalWeight = 0;
        foreach ($products as $product) {
            $totalVolume += $product['volume'] * $product['quantity'];
            $totalWeight += ($product['grams'] / 1000) * $product['quantity']; // Convert grams to kg
        }
        
        // Fallback to package weight if products don't have weight
        if ($totalWeight == 0) {
            $packageWeight = $request->getPackageWeight();
            $weightUnit = $this->_scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
            $totalWeight = $this->converter->convertToKg($packageWeight, $weightUnit);
        }

        $requestData = [
            'origin' => $origin,
            'destination' => $destination,
            'products' => $products,
            'weight' => $totalWeight,
            'volume' => $totalVolume,
        ];
        
        // Debug logging
        $this->_logger->debug("Nbox Rate Request Data:", $requestData);

        $response = $this->nboxApi->getRates($requestData);

        if (!isset($response['rates']) || !is_array($response['rates']) || empty($response['rates'])) {
            $this->_logger->debug("NboxShipping: No rates returned from API.");
            return false;
        }

        $result = $this->rateResultFactory->create();

        foreach ($response['rates'] as $item) {
            $method = $this->rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($item["service_code"]);
            $method->setMethod($item["service_code"]);
            $method->setMethodTitle(
                $item["service_name"] . " " . $item["description"]
            );
            $method->setPrice($this->getFinalPriceWithHandlingFee($item["total_price"]));
            $method->setCost($item["total_price"]);

            $result->append($method);
        }

        return $result;
    }
}
