<?php

namespace Nbox\Shipping\Plugin;

use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
//
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Utils\Converter;

class OrderPlugin
{
    protected $storeManager;
    protected $storeConfig;
    protected $productRepository;
    protected $logger;
    protected $storeSource;
    protected $nboxApi;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $storeConfig,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        StoreSource $storeSource,
        NboxApi $nboxApi
    ) {
        $this->storeManager = $storeManager;
        $this->storeConfig = $storeConfig;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->storeSource = $storeSource;
        $this->nboxApi = $nboxApi;
    }

    /**
     * Plugin for Order::place()
     * This method runs AFTER an order is placed.
     */
    public function afterPlace(Order $subject, $result)
    {
        $this->logger->debug("Plugin Triggered: Order placed!");

        $stores = $this->storeSource->getStoreShippingOrigins();
        // Get Shipping Address
        $shippingAddress = $subject->getShippingAddress();
        //
        $toSend = [
            "order" => [
                "shopDomain"      => $stores[0]['store_domain'],
                "carrier"         => str_replace("nboxshipping_", "", $subject->getShippingMethod()),
                "subTotal"        => (float) $subject->getSubtotal(),
                "orderNumber"     => intval($subject->getIncrementId()),
                "orderReference"  => $subject->getIncrementId(),
                "total"           => (float) $subject->getGrandTotal(),
                "currency"        => $subject->getOrderCurrencyCode(),
                "shippingFee"     => (float) $subject->getShippingAmount(),
            ],
            "customer" => [
                "firstName"       => $shippingAddress ? $shippingAddress->getFirstname() : '',
                "lastName"        => $shippingAddress ? $shippingAddress->getLastname() : '',
                "email"           => $subject->getCustomerEmail(),
                "phone"           => $shippingAddress ? $shippingAddress->getTelephone() : '',
            ],
            'origin' => [
                "address" => $this->storeConfig->getValue('shipping/origin/region_id', ScopeInterface::SCOPE_STORE),
                "city" => $this->storeConfig->getValue('shipping/origin/city', ScopeInterface::SCOPE_STORE),
                "zip" => $this->storeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORE),
                "countryCode" => $this->storeConfig->getValue('shipping/origin/country_id', ScopeInterface::SCOPE_STORE),
                
            ],
            "destination" => [
                "address"         => $shippingAddress ? implode(" ", $shippingAddress->getStreet()) : '',
                "city"            => $shippingAddress ? $shippingAddress->getCity() : '',
                "state"           => $shippingAddress ? $shippingAddress->getRegion() : '',
                "countryCode"     => $shippingAddress ? $shippingAddress->getCountryId() : '',
                "zip"             => $shippingAddress ? $shippingAddress->getPostcode() : '',
                "longitude"       => null,
                "latitude"        => null,
            ],
            "products" => [],
        ];

        foreach ($subject->getAllVisibleItems() as $item) {
            $product = $this->productRepository->getById($item->getProductId());
            $weightUnit = $this->storeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
            
            // Convert weight and dimensions
            $weight = $product->getWeight(); // Assuming Magento uses grams by default
            $length = $product->getCustomAttribute('length') ? $product->getCustomAttribute('length')->getValue() : 0;
            $width  = $product->getCustomAttribute('width') ? $product->getCustomAttribute('width')->getValue() : 0;
            $height = $product->getCustomAttribute('height') ? $product->getCustomAttribute('height')->getValue() : 0;

            $toSend["products"][] = [
                "name"      => $item->getName(),
                "quantity"  => (float) $item->getQtyOrdered(),
                "price"     => (float) $item->getPrice(),
                "grams"     => Converter::convertToGrams((float) $weight, $weightUnit),
                "length"    => (float) $length,
                "width"     => (float) $width,
                "height"    => (float)$height,
                "volume"    => (float) ($length * $width * $height),
                "currency"  => $subject->getOrderCurrencyCode(),
            ];
        }
        $this->logger->debug("TO SEND:", $toSend);
        

        // Log the order details (for debugging)
        $this->nboxApi->checkout($toSend);

        return $result; // Ensure original result is returned
    }
}
