<?php
namespace NBOX\Shipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use NBOX\Shipping\Helper\StoreSource;
use NBOX\Shipping\Helper\NboxApi;

/**
 * Class ShippingOriginObserver
 * Observes shipping origin events and updates NBOX API with the shipping data.
 *
 */
class ShippingOriginObserver implements ObserverInterface
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
     * ShippingOriginObserver constructor.
     *
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
     * Execute the observer to update shipping origin data to NBOX API.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('OBSERVER ACTIVATED: ');
        
        // Get all shipping origins from StoreSource
        $stores = $this->storeSource->getStoreShippingOrigins();
        $store = $stores[0];

        $data = [
            "locations" => [[
                "id"           => $store["store_code"],
                "name"         => $store["store_name"],
                "address"      => $store["address"],
                "city"         => $store["city"],
                "countryCode"  => $store["country_code"],
                "country"      => $store["country_name"],
                "state"        => $store["state"],
                "zip"          => $store["zip"],
                "phone"        => $store['phone']
            ]]
        ];

        $this->logger->info('Shipping Origin Data: ' . json_encode($data));
        
        try {
            $this->nboxApi->locations($data);
        } catch (\Exception $e) {
            $this->logger->error("Error on locations: " . $e->getMessage());
        }
    }
}
