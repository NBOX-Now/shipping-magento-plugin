<?php
 
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

 
class Nboxshipping extends AbstractCarrier implements CarrierInterface
{
 
    protected $_code = 'nboxshipping';
    /**
     * @var LoggerInterface
     */
    protected $_logger;
 
    protected $rateResultFactory;
 
    protected $rateMethodFactory;
 
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    )
    {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
    public function getAllowedMethods()
    {
        return ['nboxshipping' => $this->getConfigData('name')];
    }
 
    public function collectRates(RateRequest $request)
    {
        // $this->_logger->debug('Pramis??: ' . $request->getDestCountryId());
        if (!$this->getConfigFlag('active')) {
            return false;
        }
 
        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();
        //
       
 
        /**
         * Start custom script
         * Call NBOX Now Rates API here
         */
        $list = [
            [
                "company" => "NBOX",
                "description" => "3-5 days delivery",
                "amount" => 5.00
            ],
            [
                "company" => "FEDEX",
                "description" => "3-5 days delivery",
                "amount" => 15.00
            ],

        ];
        
        foreach($list as $item){
            $method = $this->rateMethodFactory->create();
    
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($item["company"]);
    
            $method->setMethod($item["company"]);
            $method->setMethodTitle($item["description"]);
    
            /*you can fetch shipping price from different sources over some APIs, we used price from config.xml - xml node price*/
            
            $method->setPrice($this->getFinalPriceWithHandlingFee($item["amount"]));
            $method->setCost($item["amount"]);
    
            $result->append($method);
            
        }

        return $result;
    }
}