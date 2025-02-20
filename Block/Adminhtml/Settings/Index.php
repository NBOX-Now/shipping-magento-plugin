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
    protected $configHelper;
    protected $productHelper;
    protected $storeManager;
    protected $imageHelper;
    protected $backendUrl;

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
        parent::__construct($context, $data);

        $this->configHelper = $configHelper;
        $this->productHelper = $productHelper;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->backendUrl = $backendUrl;
    }

    public function getProducts(): array
    {
        // Retrieve products filtered by ProductHelper (those needing attention)
        $products = $this->productHelper->getProducts();
        $formattedProducts = [];
        
        foreach ($products as $product) {
            $productId = $product->getId();
            $formattedProducts[] = [
                'id'              => $productId,
                'name'            => $product->getName(),
                'url'             => $this->backendUrl->getUrl('catalog/product/edit', ['id' => $productId]),
                'image_url'       => $this->getImageUrl($product),
                'has_weight'      => $product->getWeight() > 0,
                'has_dimensions'  => $this->getCustomValue($product, 'length') && $this->getCustomValue($product, 'width') && $this->getCustomValue($product, 'height'),
            ];
        }

        return $formattedProducts;
    }

    private function getCustomValue($product, string $attributeCode)
    {
        return $product->getCustomAttribute($attributeCode) ? $product->getCustomAttribute($attributeCode)->getValue() : 0;
    }

    private function getImageUrl($product): string
    {
        $imagePath = $product->getThumbnail();
        $url = '';
        if ($imagePath != '') {
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA). 'catalog/product' . $imagePath; // Correct path to product images
        }
        $product->setData('image_url', $url);
        return $url;
    }

    public function getStepsCompleted(){
        return 2;
    }
}
