<?php
namespace NBOX\Shipping\Block\Adminhtml\Settings;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
//
use NBOX\Shipping\Helper\ConfigHelper;
use NBOX\Shipping\Helper\ProductHelper;

class Index extends Template
{
    protected $configHelper;
    protected $productHelper;

    public function __construct(
        Context $context, 
        ConfigHelper $configHelper,
        ProductHelper $productHelper,
        array $data = [], 
    ){
        $data['isLoggedIn'] = $configHelper->getApiToken() ? true : false;
        parent::__construct($context, $data);
        $this->configHelper = $configHelper; 
        $this->productHelper = $productHelper; 
        //
    }

    public function getProducts(){
        return $this->productHelper->getProducts();
    }

}
