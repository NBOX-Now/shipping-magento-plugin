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

/**
 * Helper class for managing configuration settings of Nbox Shipping module.
 */
class ConfigHelper
{
    /** @var string API token config path */
    private const XML_PATH_API_TOKEN = 'nbox_shipping/auth/api_token';

    /** @var string Plugin activation status config path */
    private const XML_PATH_PLUGIN_ACTIVE = 'nbox_shipping/general/active';

    /** @var WriterInterface */
    protected $configWriter;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ConfigInterface */
    protected $configInterface;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TypeListInterface */
    protected $cacheTypeList;

    /** @var Manager */
    protected $cacheManager;

    /**
     * ConfigHelper constructor.
     *
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface $configInterface
     * @param TypeListInterface $cacheTypeList
     * @param Manager $cacheManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $configInterface,
        TypeListInterface $cacheTypeList,
        Manager $cacheManager,
        LoggerInterface $logger
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->configInterface = $configInterface;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    /**
     * Save the API token to Magento configuration.
     *
     * @param string $token API token to save.
     */
    public function saveApiToken(string $token): void
    {
        $this->configWriter->save(self::XML_PATH_API_TOKEN, $token, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->clearCache();
    }

    /**
     * Retrieve the stored API token.
     *
     * @return string|null API token if available, otherwise null.
     */
    public function getApiToken(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_TOKEN, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
    
    /**
     * Delete the stored API token.
     *
     * @return array Status of the deletion operation.
     */
    public function deleteApiToken(): array
    {
        try {
            $this->configInterface->deleteConfig(
                self::XML_PATH_API_TOKEN,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->clearCache();
            return ["status" => "success"];
        } catch (\Exception $e) {
            $this->logger->debug("Error deleting API token: " . $e->getMessage());
            return ["status" => "failed", "message" => $e->getMessage()];
        }
    }

    /**
     * Save the plugin activation status.
     *
     * @param bool $isActive True to activate, false to deactivate.
     */
    public function setPluginActive(bool $isActive): void
    {
        $this->configWriter->save(
            self::XML_PATH_PLUGIN_ACTIVE,
            $isActive ? '1' : '0',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        $this->clearCache();
    }

    /**
     * Check if the plugin is active.
     *
     * @return bool True if active, false otherwise.
     */
    public function isPluginActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_PLUGIN_ACTIVE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Clear the necessary cache types.
     */
    protected function clearCache(): void
    {
        $validCacheTypes = ['config', 'block_html', 'full_page'];

        foreach ($validCacheTypes as $type) {
            try {
                $this->cacheTypeList->cleanType($type);
            } catch (\Exception $e) {
                $this->logger->debug("Error cleaning cache for type {$type}: " . $e->getMessage());
            }
        }

        try {
            $this->cacheManager->clean([Config::CACHE_TAG]);
        } catch (\Exception $e) {
            $this->logger->debug("Error clearing config cache: " . $e->getMessage());
        }
    }
}
