<?php
declare(strict_types=1);

namespace Nbox\Shipping\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Nbox\Shipping\Utils\Converter;

/**
 * Service class for formatting address and product data for Nbox API requests
 */
class DataFormatter
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * DataFormatter constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Converter $converter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Converter $converter,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->converter = $converter;
        $this->logger = $logger;
    }

    /**
     * Format origin address from store shipping configuration
     *
     * @param int|null $storeId
     * @return array
     */
    public function formatOriginAddress($storeId = null): array
    {
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeInterface::SCOPE_STORE;
        
        $streetLine1 = $this->scopeConfig->getValue('shipping/origin/street_line1', $scope, $storeId);
        $streetLine2 = $this->scopeConfig->getValue('shipping/origin/street_line2', $scope, $storeId);
        $address = trim($streetLine1 . ' ' . $streetLine2);
        
        return [
            'address' => $address ?: '',
            'city' => $this->scopeConfig->getValue('shipping/origin/city', $scope, $storeId) ?: '',
            'state' => $this->scopeConfig->getValue('shipping/origin/region_id', $scope, $storeId) ?: null,
            'countryCode' => $this->scopeConfig->getValue('shipping/origin/country_id', $scope, $storeId) ?: '',
            'country' => null, // Could be enhanced to get full country name
            'zip' => $this->scopeConfig->getValue('shipping/origin/postcode', $scope, $storeId) ?: '',
            'longitude' => null,
            'latitude' => null,
        ];
    }

    /**
     * Format destination address from RateRequest
     *
     * @param RateRequest $request
     * @return array
     */
    public function formatDestinationFromRateRequest(RateRequest $request): array
    {
        return [
            'address' => $request->getDestStreet() ?: '',
            'city' => $request->getDestCity() ?: '',
            'state' => $request->getDestRegionCode() ?: null,
            'countryCode' => $request->getDestCountryId() ?: '',
            'country' => null, // Could be enhanced to get full country name
            'zip' => $request->getDestPostcode() ?: '',
            'longitude' => null,
            'latitude' => null,
        ];
    }

    /**
     * Format destination address from Order Address
     *
     * @param OrderAddress $shippingAddress
     * @return array
     */
    public function formatDestinationFromOrderAddress(OrderAddress $shippingAddress): array
    {
        return [
            'address' => $shippingAddress ? implode(" ", $shippingAddress->getStreet()) : '',
            'city' => $shippingAddress ? $shippingAddress->getCity() : '',
            'state' => $shippingAddress ? $shippingAddress->getRegion() : null,
            'countryCode' => $shippingAddress ? $shippingAddress->getCountryId() : '',
            'country' => null, // Could be enhanced to get full country name
            'zip' => $shippingAddress ? $shippingAddress->getPostcode() : '',
            'longitude' => null,
            'latitude' => null,
        ];
    }

    /**
     * Format products array from quote items (for rate calculation)
     *
     * @param array $items Quote items
     * @param int|null $storeId
     * @return array
     */
    public function formatProductsFromQuoteItems(array $items, $storeId = null): array
    {
        $products = [];
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeInterface::SCOPE_STORE;
        
        // Get currency and weight unit from store configuration
        $currency = $this->scopeConfig->getValue('currency/options/default', $scope, $storeId);
        $weightUnit = $this->scopeConfig->getValue('general/locale/weight_unit', $scope, $storeId);

        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById($item->getProduct()->getId());
                $formattedProduct = $this->formatSingleProductFromQuoteItem($item, $product, $currency, $weightUnit);
                if ($formattedProduct) {
                    $products[] = $formattedProduct;
                }
            } catch (\Exception $e) {
                $this->logger->error("Error formatting product: " . $e->getMessage());
            }
        }

        return $products;
    }

    /**
     * Format products array from order items
     *
     * @param array $items Order items
     * @param string $currency
     * @param int|null $storeId
     * @return array
     */
    public function formatProductsFromOrderItems(array $items, string $currency, $storeId = null): array
    {
        $products = [];
        $scope = $storeId ? ScopeInterface::SCOPE_STORES : ScopeInterface::SCOPE_STORE;
        $weightUnit = $this->scopeConfig->getValue('general/locale/weight_unit', $scope, $storeId);

        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById($item->getProductId());
                $formattedProduct = $this->formatSingleProductFromOrderItem($item, $product, $currency, $weightUnit);
                if ($formattedProduct) {
                    $products[] = $formattedProduct;
                }
            } catch (\Exception $e) {
                $this->logger->error("Error formatting product: " . $e->getMessage());
            }
        }

        return $products;
    }

    /**
     * Format single product from quote item
     *
     * @param mixed $item Quote item
     * @param mixed $product Product
     * @param string $currency
     * @param string $weightUnit
     * @return array|null
     */
    protected function formatSingleProductFromQuoteItem($item, $product, string $currency, string $weightUnit): ?array
    {
        try {
            // Get dimensions
            $length = $product->getCustomAttribute('length')
                ? (float) $product->getCustomAttribute('length')->getValue()
                : 0;
            $width = $product->getCustomAttribute('width')
                ? (float) $product->getCustomAttribute('width')->getValue()
                : 0;
            $height = $product->getCustomAttribute('height')
                ? (float) $product->getCustomAttribute('height')->getValue()
                : 0;

            // Get weight and convert to grams
            $weight = $product->getWeight() ? (float) $product->getWeight() : 0;
            $grams = $this->converter->convertToGrams($weight, $weightUnit);

            return [
                'name' => $item->getName() ?: $product->getName(),
                'quantity' => (float) $item->getQty(),
                'price' => (float) $item->getPrice(),
                'grams' => $grams,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'volume' => $length * $width * $height,
                'currency' => $currency ?: 'USD',
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error formatting single product from quote item: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Format single product from order item
     *
     * @param mixed $item Order item
     * @param mixed $product Product
     * @param string $currency
     * @param string $weightUnit
     * @return array|null
     */
    protected function formatSingleProductFromOrderItem($item, $product, string $currency, string $weightUnit): ?array
    {
        try {
            // Get dimensions
            $length = $product->getCustomAttribute('length')
                ? (float) $product->getCustomAttribute('length')->getValue()
                : 0;
            $width = $product->getCustomAttribute('width')
                ? (float) $product->getCustomAttribute('width')->getValue()
                : 0;
            $height = $product->getCustomAttribute('height')
                ? (float) $product->getCustomAttribute('height')->getValue()
                : 0;

            // Get weight and convert to grams
            $weight = $product->getWeight() ? (float) $product->getWeight() : 0;
            $grams = $this->converter->convertToGrams($weight, $weightUnit);

            return [
                'name' => $item->getName(),
                'quantity' => (float) $item->getQtyOrdered(),
                'price' => (float) $item->getPrice(),
                'grams' => $grams,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'volume' => $length * $width * $height,
                'currency' => $currency,
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error formatting single product from order item: " . $e->getMessage());
            return null;
        }
    }
}
