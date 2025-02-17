<?php
namespace Nbox\Shipping\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper
{
    const XML_PATH_API_TOKEN = 'nbox_shipping/auth/api_token';
    const XML_PATH_PLUGIN_ACTIVE = 'nbox_shipping/general/active';

    protected $configWriter;
    protected $scopeConfig;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Save API Token Password
     */
    public function saveApiToken($token)
    {
        $this->configWriter->save(self::XML_PATH_API_TOKEN, $token, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Get API Token Password
     */
    public function getApiToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN, ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Save Plugin Activation Status
     */
    public function setPluginActive($isActive)
    {
        $this->configWriter->save(self::XML_PATH_PLUGIN_ACTIVE, $isActive ? '1' : '0', ScopeInterface::SCOPE_WEBSITES);
    }

    /**
     * Check if Plugin is Active
     */
    public function isPluginActive()
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_PLUGIN_ACTIVE, ScopeInterface::SCOPE_WEBSITES);
    }
}
