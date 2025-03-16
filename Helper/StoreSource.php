<?php
namespace Nbox\Shipping\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

class StoreSource
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CountryInformationAcquirerInterface
     */
    protected $countryInformation;

    /**
     * StoreSource constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CountryInformationAcquirerInterface $countryInformation
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CountryInformationAcquirerInterface $countryInformation
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->countryInformation = $countryInformation;
    }

    /**
     * Get the shipping origins for all stores.
     *
     * @return array
     */
    public function getStoreShippingOrigins()
    {
        $stores = $this->storeManager->getStores();
        $shippingOrigins = [];

        foreach ($stores as $store) {
            $storeId = $store->getId();

            // Get the base URL of the store
            $storeUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);

            // Extract domain using Magento's getBaseUrl method
            $storeDomain = $this->getDomainFromUrl($storeUrl);

            // Get country info
            $countryCode = $this->scopeConfig->getValue(
                'shipping/origin/country_id',
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            $countryName = $this->countryInformation->getCountryInfo($countryCode)->getFullNameLocale();

            // Add store data to the result array
            $shippingOrigins[] = [
                'website_id'   => $store->getWebsiteId(),
                'store_id'     => $store->getId(),
                'store_name'   => $store->getName(),
                'store_code'   => $store->getCode(),
                'store_url'    => $storeUrl,
                'store_domain' => $storeDomain,
                'phone'        => $this->scopeConfig->getValue(
                    'general/store_information/phone',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ),
                'address'      => $this->scopeConfig->getValue(
                    'shipping/origin/street_line1',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ) . ", " . $this->scopeConfig->getValue(
                    'shipping/origin/street_line2',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ),
                'city'         => $this->scopeConfig->getValue(
                    'shipping/origin/city',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ),
                'state'        => $this->scopeConfig->getValue(
                    'shipping/origin/region_id',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ),
                'zip'          => $this->scopeConfig->getValue(
                    'shipping/origin/postcode',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ),
                'country_code' => $countryCode,
                'country_name' => $countryName
            ];
        }

        return $shippingOrigins;
    }

    /**
     * Extracts domain from a given URL.
     *
     * @param string $url
     * @return string
     */
    private function getDomainFromUrl($url)
    {
        // Use regular expression to match the domain part of the URL
        if (preg_match('/^(https?:\/\/)?([^\/]+)/i', $url, $matches)) {
            return $matches[2];  // Return the domain part (matches[2])
        }

        return 'No domain found';
    }

    /**
     * Get the shipment origin for a specific store.
     *
     * @return array
     */
    public function getShipmentOrigin()
    {
        // Use the storeId from a specific store context
        $storeId = $this->storeManager->getStore()->getId();

        return [
            'phone'        => $this->scopeConfig->getValue(
                'general/store_information/phone',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'address'      => $this->scopeConfig->getValue(
                'shipping/origin/street_line1',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ) . ", " . $this->scopeConfig->getValue(
                'shipping/origin/street_line2',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'city'         => $this->scopeConfig->getValue(
                'shipping/origin/city',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'state'        => $this->scopeConfig->getValue(
                'shipping/origin/region_id',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'zip'          => $this->scopeConfig->getValue(
                'shipping/origin/postcode',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'country_code' => $this->scopeConfig->getValue(
                'shipping/origin/country_id',
                ScopeInterface::SCOPE_STORES,
                $storeId
            ),
            'country_name' => $this->countryInformation->getCountryInfo($countryCode)->getFullNameLocale(),
        ];
    }
}
