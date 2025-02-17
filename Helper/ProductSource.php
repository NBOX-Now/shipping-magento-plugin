<?php

namespace Nbox\Shipping\Helper;

use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class ProductSource
{
   protected $sourceItemRepository;
   protected $sourceRepository;
   protected $defaultSourceProvider;
   protected $shippingConfig;
   protected $searchCriteriaBuilder;
   protected $logger;

   public function __construct(
      SourceItemRepositoryInterface $sourceItemRepository,
      SourceRepositoryInterface $sourceRepository, 
      DefaultSourceProviderInterface $defaultSourceProvider,
      ShippingConfig $shippingConfig,
      SearchCriteriaBuilder $searchCriteriaBuilder,
      LoggerInterface $logger
   ) {
      $this->sourceItemRepository = $sourceItemRepository;
      $this->sourceRepository = $sourceRepository;
      $this->defaultSourceProvider = $defaultSourceProvider;
      $this->shippingConfig = $shippingConfig;
      $this->searchCriteriaBuilder = $searchCriteriaBuilder;
      $this->logger = $logger;
   }

   /**
    * Get product source locations automatically detecting MSI usage.
    *
    * @param string $sku
    * @return array
    */
   public function getProductSources($sku)
   {
      try {
         $this->logger->info("Fetching product sources for SKU: " . $sku);

         // Create search criteria for fetching inventory sources by SKU
         $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $sku, 'eq')
            ->create();

         // Fetch source items
         $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

         foreach ($sourceItems as $sourceItem) {
            $sourceCode = $sourceItem->getSourceCode();

            if ($sourceItem->getStatus() == SourceItemInterface::STATUS_IN_STOCK) {
               // Fetch and return the first valid source details
               return $this->getSourceDetails($sourceCode);
            }
         }

         // If no valid MSI sources found, return default shipping origin details
         $defaultSourceCode = $this->defaultSourceProvider->getCode();
         return $this->getSourceDetails($defaultSourceCode);
      } catch (\Exception $e) {
            $this->logger->error("Error fetching product sources for SKU {$sku}: " . $e->getMessage());
            return [
               'error' => "Could not retrieve source details"
            ];
      }
   }

   /**
    * Fetch source location details (address) by source code
    *
    * @param string $sourceCode
    * @return array
    */
   public function getSourceDetails($sourceCode)
   {
      try {
         $source = $this->sourceRepository->get($sourceCode);

         return [
               'source_code'   => $source->getSourceCode(),
               'name'          => $source->getName(),
               'latitude'      => $source->getLatitude(),
               'longitude'     => $source->getLongitude(),
               'country'       => $source->getCountryId(),
               'region'        => $source->getRegionId(),
               'city'          => $source->getCity(),
               'postcode'      => $source->getPostcode(),
               'street'        => $source->getStreet(),
               'contact_name'  => $source->getContactName(),
               'contact_phone' => $source->getPhone()
         ];
      } catch (\Exception $e) {
         $this->logger->error("Error fetching source details for {$sourceCode}: " . $e->getMessage());
         return ['error' => "Could not retrieve source details"];
      }
   }
}
