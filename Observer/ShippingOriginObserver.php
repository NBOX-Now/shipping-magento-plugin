<?php
namespace NBOX\Shipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use NBOX\Shipping\Helper\StoreSource;
use NBOX\Shipping\Helper\NboxApi;

class ShippingOriginObserver implements ObserverInterface
{
    protected $logger;
    protected $storeSource;
    protected $nboxApi;

    public function __construct(
        LoggerInterface $logger,
        StoreSource $storeSource,
        NboxApi $nboxApi
    ) {
        $this->logger = $logger;
        $this->storeSource = $storeSource;
        $this->nboxApi = $nboxApi;
    }

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
      try{
         $this->nboxApi->locations($data);
      } catch (\Exception $e) {
         $this->logger->error("Error on locations: " . $e->getMessage());
      }
    }
}
