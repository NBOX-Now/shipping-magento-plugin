<?php

namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
//
use Magento\Store\Model\StoreManagerInterface;
//
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Helper\ConfigHelper;

class Activation extends Action implements HttpPostActionInterface
{
   protected $resultRedirectFactory;
   protected $configHelper;
   protected $messageManager;
   protected $request;
   protected $session;
   protected $scopeConfig;
   protected $storeSource;
   protected $nboxApi;
   
   public function __construct(
      Context $context,
      RedirectFactory $resultRedirectFactory,
      ConfigHelper $configHelper,
      ManagerInterface $messageManager,
      RequestInterface $request,
      StoreSource $storeSource,
      SessionManagerInterface $session,
      ScopeConfigInterface $scopeConfig,
      NboxApi $nboxApi
   ) {
      parent::__construct($context);
      $this->resultRedirectFactory = $resultRedirectFactory;
      $this->configHelper = $configHelper;
      $this->messageManager = $messageManager;
      $this->request = $request;
      $this->session = $session;
      $this->scopeConfig = $scopeConfig;
      $this->storeSource = $storeSource;
      $this->nboxApi = $nboxApi;
   }

   public function execute()
   {
      $activate = intval($this->request->getParam('isActive'));
      $activateBoolean = (bool) $activate;
      
      $stores = $this->storeSource->getStoreShippingOrigins();
      $store = $stores[0];
      
      $requestData = [
         "activate"  => !$activateBoolean,
         "locations" => [[
                           "id"           => $store["store_code"],
                           "name"         => $store["store_name"],
                           "address"      => $store["address"],
                           "city"         => $store["city"],
                           "countyCode"   => $store["country_code"],
                           "country"      => $store["country_name"],
                           "state"        => $store["state"],
                           "zip"          => $store["zip"],
                           "phone"        => $store['phone']
                        ]]
      ];
      // echo "<pre>"; var_dump($requestData); echo "</pre>";

      $response = $this->nboxApi->activate($requestData);
      //
      if ($response['status'] === 'success') {
         // Store login token in Magento config
         $this->configHelper->setPluginActive(!$activateBoolean);
         $this->messageManager->addSuccessMessage(__('Activation/Deactivation complete.'));
      } else {
         $message = isset($response['message']) ? $response['message'] : __("Activation/Deactivation failed.");
         $this->messageManager->addErrorMessage($message);
      }

      return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
   }
}
