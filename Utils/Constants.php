<?php 
namespace NBOX\Shipping\Utils;

class Constants
{
   const MODULE_NAME = 'NBOX_Shipping';

   const NBOX_BASE_URL           = 'https://nbox.now/';
   const NBOX_SUPPORT_EMAIL      = "info@nbox.qa";
   const NBOX_RATES              = self::NBOX_BASE_URL.'api/rates';
   const NBOX_LOGIN              = self::NBOX_BASE_URL.'api/login';
   const NBOX_ACTIVATION         = self::NBOX_BASE_URL.'api/activation';
   const NBOX_FULFILLED          = self::NBOX_BASE_URL.'api/fulfilled';
   const NBOX_CANCELLED          = self::NBOX_BASE_URL.'api/order/cancelled';
   const NBOX_ORDER              = self::NBOX_BASE_URL.'api/order';
   const NBOX_LOCATIONS          = self::NBOX_BASE_URL.'api/locations/update';
   const NBOX_NOW_SIGNUP_URL     = self::NBOX_BASE_URL.'signup';
   const NBOX_NOW_DASHBOARD_URL  = self::NBOX_BASE_URL.'dashboard';
   const NBOX_NOW_HEADER_TOKEN   = "x-nbox-shop-token";
   const NBOX_NOW_HEADER_DOMAIN  = "x-nbox-shop-domain";
}