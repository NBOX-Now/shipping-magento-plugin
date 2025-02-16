<?php

namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
//
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\UrlInterface;

class Login extends Action implements HttpPostActionInterface
{
   protected $resultRedirectFactory;
   protected $configWriter;
   protected $messageManager;
   protected $request;
   protected $session;
   protected $scopeConfig;
   protected $storeManager;
   protected $countryFactory;
   protected $regionFactory;

   public function __construct(
      Context $context,
      RedirectFactory $resultRedirectFactory,
      WriterInterface $configWriter,
      ManagerInterface $messageManager,
      RequestInterface $request,
      SessionManagerInterface $session,
      ScopeConfigInterface $scopeConfig,
      StoreManagerInterface $storeManager,
      CountryFactory $countryFactory,
      RegionFactory $regionFactory
   ) {
      parent::__construct($context);
      $this->resultRedirectFactory = $resultRedirectFactory;
      $this->configWriter = $configWriter;
      $this->messageManager = $messageManager;
      $this->request = $request;
      $this->session = $session;
      $this->scopeConfig = $scopeConfig;
      $this->storeManager = $storeManager;
      $this->countryFactory = $countryFactory;
      $this->regionFactory = $regionFactory;
   }

   public function execute()
   {
      $username = $this->request->getParam('username');
      $password = $this->request->getParam('password');

      // Get shop details from Magento setup
      $stores = $this->storeManager->getStores();

      foreach($stores as $store){
         echo "<pre>"; var_dump($store->getId()); echo "</pre>";
         echo "<pre>"; var_dump($store->getName()); echo "</pre>";
         echo "<pre>"; var_dump($store->getBaseUrl()); echo "</pre>";
         echo "<pre>"; var_dump($store->getCode()); echo "</pre>";
         echo "<pre>"; var_dump($store->getBaseUrl(UrlInterface::URL_TYPE_LINK)); echo "</pre>";
         $storeUrl = $store->getBaseUrl();

        // Parse the URL and get only the domain (host)
        $parsedUrl = parse_url($storeUrl);
        $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'No domain found';
        echo "<pre>"; var_dump($domain); echo "</pre>";
      }
      
      exit;

      if (!$username || !$password) {
         $this->messageManager->addErrorMessage(__('Invalid credentials.'));
         return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
      }

      // Call your API for authentication
      $apiUrl = 'http://localhost:5173/api/login';
      $response = $this->makeApiRequest($apiUrl, $username, $password);

      if ($response['status'] === 'success') {
         // Store login token in Magento config
         $this->configWriter->save('nbox_shipping/general/api_token', $response['token']);
         $this->messageManager->addSuccessMessage(__('Login successful.'));
      } else {
         $this->messageManager->addErrorMessage(__('Login failed. Please check your credentials.'));
      }

      return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
   }

   private function makeApiRequest($url, $username, $password)
   {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $username, 'password' => $password]));
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

      $response = curl_exec($ch);
      curl_close($ch);

      return json_decode($response, true);
   }
}
