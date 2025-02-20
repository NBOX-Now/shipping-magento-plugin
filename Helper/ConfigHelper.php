<?php
namespace Nbox\Shipping\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config;

use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ConfigHelper
{
    const XML_PATH_API_TOKEN = 'nbox_shipping/auth/api_token';
    const XML_PATH_PLUGIN_ACTIVE = 'nbox_shipping/general/active';

    protected $configWriter;
    protected $scopeConfig;
    protected $configInterface;
    protected $logger;
    protected $cacheTypeList;
    protected $cacheManager;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $configInterface,
        TypeListInterface $cacheTypeList,
        Manager $cacheManager,
        LoggerInterface $logger,
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    public function saveApiToken($token)
    {
        // Save the API token to config
        $this->configWriter->save(self::XML_PATH_API_TOKEN, $token, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        
        // Clear Magento cache
        $this->clearCache();
    }

    /**
     * Get API Token Password
     */
    public function getApiToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
    
    public function deleteApiToken()
    {
        try {
            $this->configInterface->deleteConfig(self::XML_PATH_API_TOKEN, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            
            $this->clearCache();
            return ["status" => "success"];
        } catch (\Exception $e) {
            $this->logger->debug("Error on deleting token: " . $e->getMessage());
            return ["status" => "failed", "message" => $e->getMessage()];
        }
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
    /**
     * Clear the necessary cache types
     */
    protected function clearCache()
    {
        // Get all cache types available from the cache type list
        $cacheTypes = $this->cacheTypeList->getTypes();
    
        // Define valid cache types for clearing
        $validCacheTypes = ['config', 'block_html', 'full_page'];
    
        // Loop over valid cache types and clean them
        foreach ($validCacheTypes as $type) {
            // Ensure the cache type exists before cleaning it
            if (isset($cacheTypes[$type])) {
                try {
                    $this->cacheTypeList->cleanType($type);
                } catch (\Exception $e) {
                    $this->logger->debug("Error cleaning cache for type {$type}: " . $e->getMessage());
                }
            }
        }
    
        // Clear all configuration-related cache tags
        try {
            $this->cacheManager->clean([Config::CACHE_TAG]);
        } catch (\Exception $e) {
            $this->logger->debug("Error clearing config cache: " . $e->getMessage());
        }
    }
}
