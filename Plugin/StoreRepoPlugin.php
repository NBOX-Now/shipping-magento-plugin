<?php
namespace NBOX\Shipping\Plugin;

use Magento\Store\Model\ResourceModel\StoreRepository;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
//
use NBOX\Shipping\Helper\StoreSource;
use NBOX\Shipping\Helper\NboxApi;

class StoreRepoPlugin
{
    protected $logger;
    protected $storeSource;
    protected $NboxApi;

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
     * Plugin for StoreRepository::save()
     * Runs after a store is saved in Magento.
     */
    public function afterSave(StoreRepository $subject, $result, StoreInterface $store)
    {
      $this->logger->debug('STORE UPDATE BABY!: ');
      return
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
            // $this->apiHelper->sendStoreData($updatedStore);
        }

        return $result;
    }
}
