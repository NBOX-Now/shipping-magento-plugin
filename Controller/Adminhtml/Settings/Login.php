<?php

namespace Nbox\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Helper\ConfigHelper;
use Psr\Log\LoggerInterface;

/**
 * Login action for the Nbox Shipping settings page.
 */
class Login extends Action implements HttpPostActionInterface
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreSource
     */
    protected $storeSource;

    /**
     * @var NboxApi
     */
    protected $nboxApi;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Login constructor.
     *
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ConfigHelper $configHelper
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param StoreSource $storeSource
     * @param SessionManagerInterface $session
     * @param ScopeConfigInterface $scopeConfig
     * @param NboxApi $nboxApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager,
        RequestInterface $request,
        StoreSource $storeSource,
        SessionManagerInterface $session,
        ScopeConfigInterface $scopeConfig,
        NboxApi $nboxApi,
        LoggerInterface $logger
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
        $this->logger = $logger;
    }

    /**
     * Execute the login action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Retrieve credentials from the request
        $username = $this->request->getParam('username');
        $password = $this->request->getParam('password');

        // Get shop details from Magento setup
        $stores = $this->storeSource->getStoreShippingOrigins();

        if (!$username || !$password) {
            $this->messageManager->addErrorMessage(__('Invalid credentials.'));
            return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
        }

        if (empty($stores)) {
            $this->messageManager->addErrorMessage(__('No store configurations found.'));
            return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
        }

        // Use first store for main shop information
        $primaryStore = $stores[0];

        // Build locations array for all stores
        $locations = [];
        foreach ($stores as $store) {
            $locations[] = [
                "refId"        => $store["store_code"],
                "refName"      => $store["store_name"] . " (" . $store["store_code"] . ")",
                "address"      => $store["address"],
                "city"         => $store["city"],
                "countryCode"  => $store["country_code"],
                "country"      => $store["country_name"],
                "state"        => $store["state"],
                "zip"          => $store["zip"],
                "phone"        => $store['phone']
            ];
        }

        // Prepare request data for API login
        $requestData = [
            "email"     => $username,
            "password"  => $password,
            "name"      => $primaryStore["store_name"],
            "shopId"    => $primaryStore["store_domain"],
            "url"       => $primaryStore["store_url"],
            "platform"  => "magento",
            "locations" => $locations
        ];

        // Log the payload for debugging
        $this->logger->info('NBOX Login Payload: ' . json_encode($requestData, JSON_PRETTY_PRINT));

        // Call your API for authentication
        $response = $this->nboxApi->login($requestData);

        if ($response['status'] === 'success') {
            // Store login token in Magento config
            $this->configHelper->saveApiToken($response['token']);
            $this->messageManager->addSuccessMessage(__('Login successful.'));
        } else {
            $message = isset($response['message'])
                ? $response['message']
                : __("Login failed. Please check your credentials.");
            $this->messageManager->addErrorMessage($message);
        }

        return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
    }
}
