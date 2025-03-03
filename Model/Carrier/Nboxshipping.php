<?php
declare(strict_types=1);
 
namespace NBOX\Shipping\Model\Carrier;
 
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
//
use Nbox\Shipping\Helper\ProductSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Helper\ConfigHelper;
use Nbox\Shipping\Utils\Converter;
 
class Nboxshipping extends AbstractCarrier implements CarrierInterface {
 
    protected $_code = 'nboxshipping';
    /**
     * @var LoggerInterface
     */
    protected $_logger;
    protected $_curl;
    protected $productRepository;
    protected $rateResultFactory;
    protected $rateMethodFactory;
    protected $productSource;
    protected $configHelper;
    protected $nboxApi;
 
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
        array $data = []
    ){
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->productRepository = $productRepository;
        $this->configHelper = $configHelper;
        $this->nboxApi = $nboxApi;
        $this->_curl = $curl; 
        $this->productSource = $productSource;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

    }
 
    public function getAllowedMethods(){
        return ['nboxshipping' => $this->getConfigData('name')];
    }
 
    public function collectRates(RateRequest $request){
        if (!$this->getConfigFlag('active') || !$this->configHelper->isPluginActive()) {
            $this->_logger->debug("Stopped NBOX Method");
            return false;
        }
        /**
         * Start custom script
         * Call NBOX Now Rates API here
         */

        $items = $request->getAllItems();
        //
        $origin;
        $totalVolume = 0;
        // Prepare volume
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->getProduct()->getId());
            $sku = $product->getSku(); 
            
            // Get sources dynamically
            // $sourceLocation[$sku] = $sources;
            $origin = $this->productSource->getProductSources($sku);
            
            if ($item->getProductType() == Type::TYPE_SIMPLE) {
                $length = $product->getCustomAttribute('length') ? $product->getCustomAttribute('length')->getValue() : 0;
                $width = $product->getCustomAttribute('width') ? $product->getCustomAttribute('width')->getValue() : 0;
                $height = $product->getCustomAttribute('height') ? $product->getCustomAttribute('height')->getValue() : 0;
        
                $dimensions[] = [
                    'length' => $length,
                    'width' => $width,
                    'height' => $height
                ];
                $totalVolume += ($length * $width * $height);
            }
        }
        // $this->_logger->debug("ORIGIN: " . $origin["name"]);
        // $this->_logger->debug("ORIGIN: " . json_encode($origin));
        
        // Prepare weight
        $weight = $request->getPackageWeight();
        $weightUnit = $this->_scopeConfig->getValue('general/locale/weight_unit',ScopeInterface::SCOPE_STORE);
        // $this->_logger->debug("WEIGHT: " . $weightUnit);
        $weight = Converter::convertToKg($weight, $weightUnit);
        // $this->_logger->debug("tapos: " . $weight);
        
        $requestData = [
            'origin' => [
                "address" => $this->_scopeConfig->getValue('shipping/origin/region_id', ScopeInterface::SCOPE_STORE),
                "city" => $this->_scopeConfig->getValue('shipping/origin/city', ScopeInterface::SCOPE_STORE),
                "zip" => $this->_scopeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORE),
                "countryCode" => $this->_scopeConfig->getValue('shipping/origin/country_id', ScopeInterface::SCOPE_STORE),
                
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

        foreach($response['rates'] as $item){
            $method = $this->rateMethodFactory->create();
            // 
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($item["service_code"]);
            $method->setMethod($item["service_code"]);
            $method->setMethodTitle($item["service_name"] . " " . $item["description"]);
            $method->setPrice($this->getFinalPriceWithHandlingFee($item["total_price"]));
            $method->setCost($item["total_price"]);
            // 
            $result->append($method);
        }
        return $result;
    }
}