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
 
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ProductRepository $productRepository,
        Curl $curl,
        array $data = []
    ){
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->productRepository = $productRepository;
        $this->_curl = $curl; 
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
    public function getAllowedMethods(){
        return ['nboxshipping' => $this->getConfigData('name')];
    }
 
    public function collectRates(RateRequest $request){
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        /**
         * Start custom script
         * Call NBOX Now Rates API here
         */

        $items = $request->getAllItems();
        //
        $this->_logger->debug("converted: " . json_encode($items[0]));


        $dimensions = [];
        
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->getProduct()->getId());
            $this->_logger->debug("ITEM: " . json_encode($product->getCustomAttribute('length')->getValue()));
            
        //     if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
        
                
        //         $length = $product->getCustomAttribute('length') ? $product->getCustomAttribute('length')->getValue() : 0;
        //         $width = $product->getCustomAttribute('width') ? $product->getCustomAttribute('width')->getValue() : 0;
        //         $height = $product->getCustomAttribute('height') ? $product->getCustomAttribute('height')->getValue() : 0;
        //         $weight = $product->getWeight();
        
        //         $dimensions[] = [
        //             'length' => $length,
        //             'width' => $width,
        //             'height' => $height,
        //             'weight' => $weight
        //         ];
        //     }
        }


        // Prepare weight
        $weight = $request->getPackageWeight();
        $weightUnit = $this->_scopeConfig->getValue('general/locale/weight_unit',ScopeInterface::SCOPE_STORE);
        //
        
        //
        $weight = $this->convertToKg($weight, $weightUnit);

        $this->_logger->debug("converted: " . $weight);

        try{
            $apiUrl = 'https://nbox.now/api/rates'; 
            // $apiUrl = 'https://vu41fq-ip-37-186-50-132.tunnelmole.net/api/rates'; 
            
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
                'dimensions' => [
                    'length' => 10,
                    'width' => 10,
                    'height' => 10
                ],
            ];

            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type" => "application/json",
                'x-nbox-shop-domain' => "magento-website"//get_option("nbox_now_account_shop")
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

            $response = curl_exec($ch);
            curl_close($ch);

            $decoded = json_decode($response, true);

            $this->_logger->debug('API Response: ' . $decoded['status']);
            
            $result = $this->rateResultFactory->create();

            foreach($decoded['rates'] as $item){
                $method = $this->rateMethodFactory->create();
        
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($item["service_code"]);
        
                $method->setMethod($item["service_code"]);
                $method->setMethodTitle($item["service_name"] . " " . $item["description"]);
        
                /*you can fetch shipping price from different sources over some APIs, we used price from config.xml - xml node price*/
                
                $method->setPrice($this->getFinalPriceWithHandlingFee($item["total_price"]));
                $method->setCost($item["total_price"]);
        
                $result->append($method);
                //
            }
            return $result;
        } catch (\Exception $e) {
            $this->_logger->debug('NBOX Rates Error: ' . $e->getMessage());
        }
    }

    public function getDimensionUnit(){
        $locale = $this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);
        $this->_logger->debug('Locale: ' . $locale);

        // Countries using metric (cm)
        $metricCountries = ['GB', 'DE', 'FR', 'QA', 'AE', 'IN']; // Add more as needed

        // Extract country code
        $countryCode = strtoupper(substr($locale, -2));

        return in_array($countryCode, $metricCountries) ? 'cm' : 'in';
    }

    private static $weightToKg = [
        'kg'  => 1,         // Kilograms
        'g'   => 0.001,     // Grams
        'mg'  => 0.000001,  // Milligrams
        'lbs' => 0.453592,  // Pounds
        'oz'  => 0.0283495, // Ounces
        'ton' => 1000,      // Metric tons
    ];

    // Conversion factors to CM
    private static $lengthToCm = [
        'cm'  => 1,        // Centimeters
        'm'   => 100,      // Meters
        'mm'  => 0.1,      // Millimeters
        'in'  => 2.54,     // Inches
        'ft'  => 30.48,    // Feet
        'yd'  => 91.44,    // Yards
    ];

    /**
     * Convert weight to kilograms
     * @param float $value - weight value
     * @param string $unit - weight unit (kg, g, mg, lbs, oz, ton)
     * @return float - converted value in kg
     */
    public static function convertToKg($value, $unit)
    {
        $unit = strtolower($unit);
        if (isset(self::$weightToKg[$unit])) {
            return $value * self::$weightToKg[$unit];
        }
        throw new Exception("Unsupported weight unit: $unit");
    }

    /**
     * Convert length to centimeters
     * @param float $value - length value
     * @param string $unit - length unit (cm, m, mm, in, ft, yd)
     * @return float - converted value in cm
     */
    public static function convertToCm($value, $unit)
    {
        $unit = strtolower($unit);
        if (isset(self::$lengthToCm[$unit])) {
            return $value * self::$lengthToCm[$unit];
        }
        throw new Exception("Unsupported length unit: $unit");
    }
}