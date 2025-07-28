<?php
declare(strict_types=1);

namespace Nbox\Shipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Helper class for determining product types and shipping requirements
 */
class ProductTypeHelper extends AbstractHelper
{
    /**
     * Product types that do not require physical shipping
     */
    private const NON_SHIPPABLE_TYPES = ['virtual', 'downloadable', 'grouped'];

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProductTypeHelper constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Determine if a product requires physical shipping
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isShippableProduct(ProductInterface $product): bool
    {
        // Primary check: Product type
        if (in_array($product->getTypeId(), self::NON_SHIPPABLE_TYPES)) {
            return false;
        }

        // Secondary check: hasWeight capability from product type instance
        try {
            return $product->getTypeInstance()->hasWeight();
        } catch (\Exception $e) {
            $this->logger->warning(
                'Error checking weight capability for product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
            // Fallback: assume shippable if we can't determine
            return true;
        }
    }

    /**
     * Determine if a product is virtual/non-physical
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isVirtualProduct(ProductInterface $product): bool
    {
        return !$this->isShippableProduct($product);
    }

    /**
     * Filter array of quote items to include only shippable products
     *
     * @param array $items Array of quote items
     * @return array Filtered array containing only shippable items
     */
    public function filterShippableQuoteItems(array $items): array
    {
        return array_filter($items, function($item) {
            return $this->isShippableFromQuoteItem($item);
        });
    }

    /**
     * Filter array of order items to include only shippable products
     *
     * @param array $items Array of order items
     * @return array Filtered array containing only shippable items
     */
    public function filterShippableOrderItems(array $items): array
    {
        return array_filter($items, function($item) {
            return $this->isShippableFromOrderItem($item);
        });
    }

    /**
     * Check if a quote item represents a shippable product
     *
     * @param QuoteItem $item
     * @return bool
     */
    public function isShippableFromQuoteItem(QuoteItem $item): bool
    {
        try {
            $product = $item->getProduct();
            if (!$product || !$product->getId()) {
                // Try to load product if not already loaded
                $product = $this->productRepository->getById($item->getProductId());
            }
            return $this->isShippableProduct($product);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error checking shippability for quote item: ' . $item->getProductId(),
                ['exception' => $e->getMessage()]
            );
            // Fallback: assume shippable if we can't determine
            return true;
        }
    }

    /**
     * Check if an order item represents a shippable product
     *
     * @param OrderItem $item
     * @return bool
     */
    public function isShippableFromOrderItem(OrderItem $item): bool
    {
        try {
            $product = $this->productRepository->getById($item->getProductId());
            return $this->isShippableProduct($product);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error checking shippability for order item: ' . $item->getProductId(),
                ['exception' => $e->getMessage()]
            );
            // Fallback: assume shippable if we can't determine
            return true;
        }
    }

    /**
     * Get list of shippable product types
     *
     * @return array
     */
    public function getShippableProductTypes(): array
    {
        // Return types that are NOT in the non-shippable list
        $allTypes = ['simple', 'configurable', 'bundle', 'virtual', 'downloadable', 'grouped'];
        return array_diff($allTypes, self::NON_SHIPPABLE_TYPES);
    }

    /**
     * Get list of non-shippable product types
     *
     * @return array
     */
    public function getNonShippableProductTypes(): array
    {
        return self::NON_SHIPPABLE_TYPES;
    }

    /**
     * Check if a product type requires shipping
     *
     * @param string $productType
     * @return bool
     */
    public function isShippableProductType(string $productType): bool
    {
        return !in_array($productType, self::NON_SHIPPABLE_TYPES);
    }
}