<?php 

namespace NBOX\Shipping\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use NBOX\Shipping\Utils\Constants;

class NboxApi 
{
    protected $storeSource;
    protected $configHelper;

    /**
     * Constructor
     */
    public function __construct(
        StoreSource $storeSource,
        ConfigHelper $configHelper
    ) {
        $this->storeSource = $storeSource;
        $this->configHelper = $configHelper;
    }

    /**
     * Get Logger Instance
     *
     * @return LoggerInterface
     */
    private static function getLogger()
    {
        return ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Handle POST requests
     *
     * @param string $url
     * @param array $requestData
     * @param array $headers (optional)
     * @return array
     */
    private function makePostRequest($url, $requestData, $headers = null)
   {
      self::getLogger()->debug("Sending POST request to: {$url}");
      self::getLogger()->debug("Request Data: " . json_encode($requestData));

      try {
         // Default headers if none are provided
         if ($headers === null) {
               $headers = [
                  'Content-Type: application/json',
                  Constants::NBOX_NOW_HEADER_DOMAIN . ': ' . $this->getStoreDomain(),
                  Constants::NBOX_NOW_HEADER_TOKEN . ': ' . $this->getApiToken()
               ];
         }

         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

         $response = curl_exec($ch);
         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

         if ($response === false) {
               $errorMessage = curl_error($ch);
               $errorCode = curl_errno($ch);
               curl_close($ch);
               throw new \Exception("cURL Error #{$errorCode}: {$errorMessage}");
         }

         curl_close($ch);

         if ($httpCode < 200 || $httpCode >= 300) {
               throw new \Exception("HTTP Error {$httpCode}: {$response}");
         }

         return json_decode($response, true);
      } catch (\Exception $e) {
         self::getLogger()->debug("NBOX API Error: " . $e->getMessage());
         return ["status" => "failed", "message" => $e->getMessage()];
      }
   }


    /**
     * Get store domain for API headers
     *
     * @return string
     */
    private function getStoreDomain()
    {
        $stores = $this->storeSource->getStoreShippingOrigins();
        return !empty($stores) ? $stores[0]['store_domain'] : 'default-domain';
    }

    /**
     * Get API token for authorization
     *
     * @return string
     */
    private function getApiToken()
    {
        return $this->configHelper->getApiToken() ?? 'default-token';
    }

    /**
     * Login request
     *
     * @param array $requestData
     * @return array
     */
    public function login($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_LOGIN, $requestData, ['Content-Type: application/json']);
    }

    /**
     * Get shipping rates
     *
     * @param array $requestData
     * @return array
     */
    public function getRates($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_RATES, $requestData);
    }
}
