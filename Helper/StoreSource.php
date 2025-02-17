<?php 
namespace Nbox\Shipping\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

class StoreSource
{
   protected $scopeConfig;
   protected $storeManager;
   protected $countryInformation;

   public function __construct(
      ScopeConfigInterface $scopeConfig,
      StoreManagerInterface $storeManager,
      CountryInformationAcquirerInterface $countryInformation

   ) {
      $this->scopeConfig = $scopeConfig;
      $this->storeManager = $storeManager;
      $this->countryInformation = $countryInformation;
   }

   public function getStoreShippingOrigins(){

      $stores = $this->storeManager->getStores();
      $shippingOrigins = [];

      foreach ($stores as $store) {
         $storeId = $store->getId();
         // 
         $storeUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
         $parsedUrl = parse_url($storeUrl);
         $storeDomain = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'No domain found';
         // 
         $countryCode = $this->scopeConfig->getValue('shipping/origin/country_id', ScopeInterface::SCOPE_STORES, $storeId);
         $countryName = $this->countryInformation->getCountryInfo($countryCode)->getFullNameLocale();
         // 
         array_push($shippingOrigins, [
            'website_id'   => $store->getWebsiteId(),
            'store_id'     => $store->getId(),
            'store_name'   => $store->getName(),
            'store_code'   => $store->getCode(),
            'store_url'    => $storeUrl,
            'store_domain' => $storeDomain,
            'phone'        => $this->scopeConfig->getValue('general/store_information/phone',ScopeInterface::SCOPE_STORES,$storeId),
            'address'      => $this->scopeConfig->getValue('shipping/origin/street_line1', ScopeInterface::SCOPE_STORES, $storeId) . ", " . $this->scopeConfig->getValue('shipping/origin/street_line2', ScopeInterface::SCOPE_STORES, $storeId),
            'city'         => $this->scopeConfig->getValue('shipping/origin/city', ScopeInterface::SCOPE_STORES, $storeId),
            'state'        => $this->scopeConfig->getValue('shipping/origin/region_id', ScopeInterface::SCOPE_STORES, $storeId),
            'zip'          => $this->scopeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORES, $storeId),
            'country_code' => $countryCode,
            'country_name' => $countryName
         ]);
      }

      return $shippingOrigins;
   }
}
