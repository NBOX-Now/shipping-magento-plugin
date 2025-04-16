<?php

namespace Nbox\Shipping\Plugin;

use Magento\Store\Model\ResourceModel\StoreRepository;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;

/**
 * Plugin for StoreRepository::save()
 * Runs after a store is saved in Magento.
 */
class StoreRepoPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StoreSource
     */
    protected $storeSource;

    /**
     * @var NboxApi
     */
    protected $nboxApi;

    /**
     * StoreRepoPlugin constructor.
     * @param LoggerInterface $logger
     * @param StoreSource $storeSource
     * @param NboxApi $nboxApi
     */
    public function __construct(
        LoggerInterface $logger,
        StoreSource $storeSource,
        NboxApi $nboxApi
    ) {
        $this->logger = $logger;
        $this->storeSource = $storeSource;
        $this->nboxApi = $nboxApi;
    }

    /**
     * After save plugin for StoreRepository::save().
     *
     * Runs after a store is saved in Magento.
     *
     * @param StoreRepository $subject
     * @param mixed $result
     * @param StoreInterface $store
     * @return mixed
     */
    public function afterSave(StoreRepository $subject, $result, StoreInterface $store)
    {
        $this->logger->debug('STORE UPDATE BABY!: ');

        // Ensure code is executed properly before return
        $storeId = $store->getId();
        $shippingOrigins = $this->storeSource->getStoreShippingOrigins();

        // Find the updated store details
        $updatedStore = array_filter($shippingOrigins, function ($origin) use ($storeId) {
            return $origin['store_id'] == $storeId;
        });

        if (!empty($updatedStore)) {
            $this->logger->info('Store BEFORE: ' . json_encode($updatedStore));
            $updatedStore = reset($updatedStore); // Get the first element

            // Log data for debugging
            $this->logger->info('Store AFTER: ' . json_encode($updatedStore));

            // Push store data to external API
            // $this->nboxApi->sendStoreData($updatedStore);
        }

        return $result;
    }
}
