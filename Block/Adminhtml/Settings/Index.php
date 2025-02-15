<?php
namespace NBOX\Shipping\Block\Adminhtml\Settings;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Index extends Template
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getUsername()
    {
        // Return a default value or fetch from config
        return 'defaultUsername'; // Replace with actual config fetching logic
    }
}
