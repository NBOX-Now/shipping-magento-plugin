<?php
namespace NBOX\Shipping\Block\Adminhtml\Settings;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use NBOX\Shipping\Helper\ConfigHelper;

class Index extends Template
{
    protected $configHelper;

    public function __construct(
        Context $context, 
        ConfigHelper $configHelper,
        array $data = [], 
    ){
        $data['isLoggedIn'] = $configHelper->getApiToken() ? true : false;
        parent::__construct($context, $data);
        $this->configHelper = $configHelper; 
        //
    }
}
