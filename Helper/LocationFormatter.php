<?php

namespace Nbox\Shipping\Helper;

use Nbox\Shipping\Helper\StoreSource;

/**
 * Helper class for formatting store locations for API requests.
 */
class LocationFormatter
{
    /**
     * @var StoreSource
     */
    protected $storeSource;

    /**
     * LocationFormatter constructor.
     *
     * @param StoreSource $storeSource
     */
    public function __construct(
        StoreSource $storeSource
    ) {
        $this->storeSource = $storeSource;
    }

    /**
     * Get formatted locations array for all stores.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormattedLocations()
    {
        $stores = $this->storeSource->getStoreShippingOrigins();

        if (empty($stores)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No store configurations found.'));
        }

        $locations = [];
        foreach ($stores as $store) {
            $locations[] = [
                "refId"        => $store["store_code"],
                "refName"      => $store["store_name"] . " (" . $store["store_code"] . ")",
                "address"      => $store["address"],
                "city"         => $store["city"],
                "countryCode"  => $store["country_code"],
                "country"      => $store["country_name"],
                "state"        => $store["state"],
                "zip"          => $store["zip"],
                "phone"        => $store['phone']
            ];
        }

        return $locations;
    }

    /**
     * Get primary store information for main shop details.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPrimaryStore()
    {
        $stores = $this->storeSource->getStoreShippingOrigins();

        if (empty($stores)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No store configurations found.'));
        }

        return $stores[0];
    }

    /**
     * Get all stores data.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllStores()
    {
        $stores = $this->storeSource->getStoreShippingOrigins();

        if (empty($stores)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No store configurations found.'));
        }

        return $stores;
    }
}
