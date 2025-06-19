<?php
namespace Nbox\Shipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Helper\LocationFormatter;

/**
 * Class ShippingOriginObserver
 * Observes shipping origin events and updates Nbox API with the shipping data.
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
     * @var LocationFormatter
     */
    protected $locationFormatter;

    /**
     * ShippingOriginObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param StoreSource $storeSource
     * @param NboxApi $nboxApi
     * @param LocationFormatter $locationFormatter
     */
    public function __construct(
        LoggerInterface $logger,
        StoreSource $storeSource,
        NboxApi $nboxApi,
        LocationFormatter $locationFormatter
    ) {
        $this->logger = $logger;
        $this->storeSource = $storeSource;
        $this->nboxApi = $nboxApi;
        $this->locationFormatter = $locationFormatter;
    }

    /**
     * Execute the observer to update shipping origin data to Nbox API.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('OBSERVER ACTIVATED: ');
        
        try {
            // Get formatted locations using LocationFormatter
            $locations = $this->locationFormatter->getFormattedLocations();
            
            $data = [
                "locations" => $locations
            ];

            $this->logger->info('Shipping Origin Data: ' . json_encode($data));
            
            $this->nboxApi->locations($data);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error("Error getting formatted locations: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error("Error on locations API call: " . $e->getMessage());
        }
    }
}
