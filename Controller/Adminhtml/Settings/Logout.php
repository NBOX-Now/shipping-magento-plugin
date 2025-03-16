<?php

namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Nbox\Shipping\Helper\ConfigHelper;

/**
 * Logout action for the NBOX Shipping settings page.
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
     * Logout constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute the logout action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Delete API token and check response status
        $response = $this->configHelper->deleteApiToken();
        
        // Add success or error message based on the response
        if ($response["status"] === "success") {
            $this->messageManager->addSuccessMessage(__('Logout successful'));
        } else {
            $this->messageManager->addErrorMessage($response['message'] ?? __('Logout failed'));
        }

        // Redirect to settings page
        return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
    }
}
