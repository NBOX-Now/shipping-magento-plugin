<?php
namespace Nbox\Shipping\Utils;

class Constants
{
    public const MODULE_NAME = 'Nbox_Shipping';

    public const NBOX_BASE_URL           = 'https://nbox.now/';
    public const NBOX_SUPPORT_EMAIL      = "info@nbox.qa";
    public const NBOX_RATES              = self::NBOX_BASE_URL.'api/rates';
    public const NBOX_LOGIN              = self::NBOX_BASE_URL.'api/login';
    public const NBOX_ACTIVATION         = self::NBOX_BASE_URL.'api/activation';
    public const NBOX_FULFILLED          = self::NBOX_BASE_URL.'api/order/fulfilled';
    public const NBOX_CANCELLED          = self::NBOX_BASE_URL.'api/order/cancelled';
    public const NBOX_ORDER              = self::NBOX_BASE_URL.'api/order';
    public const NBOX_LOCATIONS          = self::NBOX_BASE_URL.'api/locations/update';
    public const NBOX_NOW_SIGNUP_URL     = self::NBOX_BASE_URL.'signup';
    public const NBOX_NOW_DASHBOARD_URL  = self::NBOX_BASE_URL.'dashboard';
    public const NBOX_NOW_HEADER_TOKEN   = "x-nbox-shop-token";
    public const NBOX_NOW_HEADER_DOMAIN  = "x-nbox-shop-domain";
}
