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
use Nbox\Shipping\Helper\LocationFormatter;

/**
 * Activation action for the Nbox Shipping settings page.
 */
class Activation extends Action implements HttpPostActionInterface
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
     * @var LocationFormatter
     */
    protected $locationFormatter;

    /**
     * Activation constructor.
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
     * @param LocationFormatter $locationFormatter
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
        LocationFormatter $locationFormatter
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
        $this->locationFormatter = $locationFormatter;
    }

    /**
     * Execute the activation/deactivation action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Retrieve the activation status from the request
        $activate = (int) $this->request->getParam('isActive');  // Use (int) instead of intval()
        $activateBoolean = (bool) $activate;

        try {
            // Get formatted locations using LocationFormatter
            $locations = $this->locationFormatter->getFormattedLocations();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
        }

        // Prepare request data for the API
        $requestData = [
            "activate"  => !$activateBoolean,
            "locations" => $locations
        ];

        // Call your API for activation/deactivation
        $response = $this->nboxApi->activate($requestData);

        // Check if activation was successful
        if ($response['status'] === 'success') {
            // Store activation status in Magento config
            $this->configHelper->setPluginActive(!$activateBoolean);
            $this->messageManager->addSuccessMessage(__('Activation/Deactivation complete.'));
        } else {
            $message = isset($response['message']) ? $response['message'] : __("Activation/Deactivation failed.");
            $this->messageManager->addErrorMessage($message);
        }

        return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
    }
}
