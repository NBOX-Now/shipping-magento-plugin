<?php 

namespace NBOX\Shipping\Helper;

use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
//
use NBOX\Shipping\Utils\Constants;

class NboxApi 
{
   /**
     * Get Logger Instance
     *
     * @return LoggerInterface
     */
   private static function getLogger()
   {
      return ObjectManager::getInstance()->get(LoggerInterface::class);
   }

   public static function login($requestData){
      $url = Constants::NBOX_LOGIN;
      
      self::getLogger()->debug('Sumasama na sila');

      try{
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
         curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

         $response = curl_exec($ch);
         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code

         // Check for cURL execution errors
        if ($response === false) {
            $errorMessage = curl_error($ch);
            $errorCode = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("cURL Error #{$errorCode}: {$errorMessage}");
         }

         curl_close($ch);

         // Check if the HTTP response code is an error (non-2xx status)
         if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("HTTP Error {$httpCode}: {$response}");
         }
         
         return json_decode($response, true);
         
     } catch (\Exception $e) {
         self::getLogger()->debug('NBOX Login Error: ' . $e->getMessage());
         return ["status"=>"failed", "message" => $e->getMessage()];
     }
   }

   public static function getRates($requestData){
      $url = Constants::NBOX_RATES;

      try{
            self::getLogger()->debug("Rates Request Data: " . json_encode($requestData));
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                  "Content-Type" => "application/json",
                  'x-nbox-shop-domain' => "magento-website"
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

            $response = curl_exec($ch);
            curl_close($ch);
            //
            return json_decode($response, true);
         } catch (\Exception $e) {
            $this->_logger->debug('NBOX Rates Error: ' . $e->getMessage());
            return ["status"=>"failed", "message" => $e->getMessage()];
      }
   }
}
