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
        $origin = null;
        $totalVolume = 0;
        // Prepare volume
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->getProduct()->getId());
            $sku = $product->getSku();

            // Get sources dynamically
            $origin = $this->productSource->getProductSources($sku);

            if ($item->getProductType() == Type::TYPE_SIMPLE) {
                $length = $product->getCustomAttribute('length')
                    ? $product->getCustomAttribute('length')->getValue()
                    : 0;
                $width = $product->getCustomAttribute('width')
                    ? $product->getCustomAttribute('width')->getValue()
                    : 0;
                $height = $product->getCustomAttribute('height')
                    ? $product->getCustomAttribute('height')->getValue()
                    : 0;

                $dimensions[] = [
                    'length' => $length,
                    'width' => $width,
                    'height' => $height
                ];
                $totalVolume += ($length * $width * $height);
            }
        }

        // Prepare weight
        $weight = $request->getPackageWeight();
        $weightUnit = $this->_scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        $weight = $this->converter->convertToKg($weight, $weightUnit); // Use the instance method here

        $requestData = [
            'origin' => [
                "address" => $this->_scopeConfig->getValue('shipping/origin/region_id', ScopeInterface::SCOPE_STORE),
                "city" => $this->_scopeConfig->getValue('shipping/origin/city', ScopeInterface::SCOPE_STORE),
                "zip" => $this->_scopeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORE),
                "countryCode" => $this->_scopeConfig->getValue(
                    'shipping/origin/country_id',
                    ScopeInterface::SCOPE_STORE
                ),
            ],
            'destination' => [
                "address" => $request->getDestStreet(),
                "city" => $request->getDestCity(),
                "zip" => $request->getDestPostcode(),
                "countryCode" => $request->getDestCountryId(),
            ],
            'weight' => $weight,
            'volume' => $totalVolume,
        ];

        $response = $this->nboxApi->getRates($requestData);
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
