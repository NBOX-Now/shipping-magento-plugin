<?php 

namespace Nbox\Shipping\Helper;

use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

class NboxApi 
{
   // Define API Base URL
   const BASE_URL = 'https://3elmly-ip-37-208-150-101.tunnelmole.net/api';
   const RATES = '/rates';
   const LOGIN = '/login';
   const TOKEN_PATH = 'nbox_shipping/general/api_token';

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
      $url = self::BASE_URL . self::LOGIN;
      
      self::getLogger()->debug('Sumasama na sila');

      try{
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
         curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
   
         $response = curl_exec($ch);
         curl_close($ch);
         
         return json_decode($response, true);
     } catch (\Exception $e) {
         self::getLogger()->debug('NBOX Login Error: ' . $e->getMessage());
         return ["status"=>"failed", "message" => $e->getMessage()];
     }
   }

   public static function getRates($requestData){
      $url = self::BASE_URL . self::RATES;

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
