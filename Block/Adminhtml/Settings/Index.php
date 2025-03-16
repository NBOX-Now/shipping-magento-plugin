<?php
namespace NBOX\Shipping\Block\Adminhtml\Settings;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Helper\Image;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
//
use NBOX\Shipping\Utils\Constants;
use NBOX\Shipping\Helper\ConfigHelper;
use NBOX\Shipping\Helper\ProductHelper;

/**
 * Admin settings block for NBOX Shipping
 */
class Index extends Template
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     * @param Image $imageHelper
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ProductHelper $productHelper,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        array $data = []
    ) {
        $data['supportEmail'] = Constants::NBOX_SUPPORT_EMAIL;
        $data['signUp'] = Constants::NBOX_NOW_SIGNUP_URL;
        $data['dashboardUrl'] = Constants::NBOX_NOW_DASHBOARD_URL;
        //
        $data['isLogged'] = (bool) $configHelper->getApiToken();
        $data['isActive'] = (bool) $configHelper->isPluginActive();
        parent::__construct($context, $data);

        $this->configHelper = $configHelper;
        $this->productHelper = $productHelper;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->backendUrl = $backendUrl;
    }

    /**
     * Retrieve products that need attention.
     *
     * @return array
     */
    public function getProducts(): array
    {
        $products = $this->productHelper->getProducts();
        $formattedProducts = [];
        
        foreach ($products as $product) {
            $productId = $product->getId();
            $formattedProducts[] = [
                'id'              => $productId,
                'name'            => $product->getName(),
                'url'             => $this->backendUrl->getUrl(
                    'catalog/product/edit',
                    ['id' => $productId]
                ),
                'image_url'       => $this->getImageUrl($product),
                'has_weight'      => $product->getWeight() > 0,
                'has_dimensions'  => $this->getCustomValue($product, 'length')
                    && $this->getCustomValue($product, 'width')
                    && $this->getCustomValue($product, 'height'),
            ];
        }

        return $formattedProducts;
    }

    /**
     * Retrieve custom attribute value of a product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $attributeCode
     * @return mixed
     */
    private function getCustomValue($product, string $attributeCode)
    {
        return $product->getCustomAttribute($attributeCode)
            ? $product->getCustomAttribute($attributeCode)->getValue()
            : 0;
    }

    /**
     * Retrieve the image URL of a product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function getImageUrl($product): string
    {
        $imagePath = $product->getThumbnail();
        $url = '';

        if (!empty($imagePath)) {
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                . 'catalog/product' . $imagePath;
        }

        $product->setData('image_url', $url);
        return $url;
    }

    /**
     * Determine the number of steps completed in the setup process.
     *
     * @return int
     */
    public function getStepsCompleted(): int
    {
        $stepsCompleted = 0;

        if ($this->configHelper->getApiToken()) {
            $stepsCompleted++;
        }
        if ($this->configHelper->isPluginActive()) {
            $stepsCompleted++;
        }
        if (empty($this->getProducts())) {
            $stepsCompleted++;
        }

        return $stepsCompleted;
    }
}
