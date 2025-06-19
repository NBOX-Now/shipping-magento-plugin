<?php

namespace Nbox\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Nbox\Shipping\Helper\ConfigHelper;
use Nbox\Shipping\Helper\NboxApi;

/**
 * Logout action for the Nbox Shipping settings page.
 */
class Logout extends Action implements HttpPostActionInterface
{
    /**
     * ConfigHelper instance for managing configuration-related tasks.
     *
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * MessageManager instance to handle success/error messages.
     *
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * NboxApi instance for making API calls.
     *
     * @var NboxApi
     */
    protected $nboxApi;

    /**
     * Logout constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ManagerInterface $messageManager
     * @param NboxApi $nboxApi
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager,
        NboxApi $nboxApi
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->messageManager = $messageManager;
        $this->nboxApi = $nboxApi;
    }

    /**
     * Execute the logout action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // First, deactivate the service via API
        $deactivateResponse = $this->nboxApi->deactivate();
        
        // Set plugin inactive locally regardless of API response
        $this->configHelper->setPluginActive(false);
        
        // Delete API token and check response status
        $tokenResponse = $this->configHelper->deleteApiToken();
        
        // Add success or error message based on both responses
        if ($deactivateResponse["status"] === "success" && $tokenResponse["status"] === "success") {
            $this->messageManager->addSuccessMessage(__('Logout successful'));
        } else {
            // Show specific error messages
            if ($deactivateResponse["status"] !== "success") {
                $this->messageManager->addErrorMessage(
                    $deactivateResponse['message'] ?? __('Deactivation failed')
                );
            }
            if ($tokenResponse["status"] !== "success") {
                $this->messageManager->addErrorMessage(
                    $tokenResponse['message'] ?? __('Token deletion failed')
                );
            }
        }

        // Redirect to settings page
        return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
    }
}
