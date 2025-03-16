<?php
namespace NBOX\Shipping\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Model\UrlInterface;

/**
 * ProductHelper assists in retrieving products with missing or zero dimensions/weight.
 */
class ProductHelper extends AbstractHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * ProductHelper constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        UrlInterface $backendUrl
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->backendUrl = $backendUrl;
    }

   /**
    * Get products that need attention (active, non-virtual, with null or 0 weight, length, width, or height)
    *
    * @return array
    * @throws LocalizedException
    */
    public function getProducts()
    {
        // Create filters for active products and non-virtual products
        $statusFilter = $this->filterBuilder
            ->setField('status')
            ->setValue(1)
            ->setConditionType('eq')
            ->create();

        $typeFilter = $this->filterBuilder
            ->setField('type_id')
            ->setValue('virtual')
            ->setConditionType('neq')
            ->create();

        // Create OR conditions for weight, length, width, and height being NULL or 0
        $attributes = ['weight', 'length', 'width', 'height'];
        $orFilters = [];

        foreach ($attributes as $attribute) {
            // Condition for NULL
            $orFilters[] = $this->filterBuilder
                ->setField($attribute)
                ->setConditionType('null')
                ->create();

            // Condition for 0
            $orFilters[] = $this->filterBuilder
                ->setField($attribute)
                ->setConditionType('eq')
                ->setValue(0)
                ->create();
        }

        // Build search criteria
        $this->searchCriteriaBuilder->addFilters([$statusFilter, $typeFilter]);

        // Apply OR condition for attributes (grouped under one OR condition)
        $this->searchCriteriaBuilder->addFilters($orFilters, true); // 'true' makes it an OR condition

        $searchCriteria = $this->searchCriteriaBuilder->create();

        // Fetch products
        return $this->productRepository->getList($searchCriteria)->getItems();
    }
}
