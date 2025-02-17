<?php
namespace Nbox\Shipping\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ConfigHelper
{
    const XML_PATH_API_TOKEN = 'nbox_shipping/auth/api_token';
    const XML_PATH_PLUGIN_ACTIVE = 'nbox_shipping/general/active';

    protected $configWriter;
    protected $scopeConfig;
    protected $logger;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Save API Token Password
     */
    public function saveApiToken($token)
    {
        
        $this->configWriter->save(self::XML_PATH_API_TOKEN, $token, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Get API Token Password
     */
    public function getApiToken()
    {
        $this->logger->debug("PLEASE LANG ". $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN, ScopeConfigInterface::SCOPE_TYPE_DEFAULT));
        return $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Save Plugin Activation Status
     */
    public function setPluginActive($isActive)
    {
        $this->configWriter->save(self::XML_PATH_PLUGIN_ACTIVE, $isActive ? '1' : '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Check if Plugin is Active
     */
    public function isPluginActive()
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_PLUGIN_ACTIVE, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
