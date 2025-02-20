<?php

namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Nbox\Shipping\Helper\ConfigHelper;

class Logout extends Action implements HttpPostActionInterface
{
    protected $configHelper;
    protected $messageManager;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        $response = $this->configHelper->deleteApiToken();
        
        if ($response["status"] === "success") {
            $this->messageManager->addSuccessMessage(__('Logout successful'));
        } else {
            $this->messageManager->addErrorMessage($response['message'] ?? __('Logout failed'));
        }

        return $this->resultRedirectFactory->create()->setPath('nbox_shipping/settings/index');
    }
}
