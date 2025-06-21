<?php
namespace Nbox\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Save action for the Nbox Shipping settings page
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Admin resource for settings access
     */
    public const ADMIN_RESOURCE = 'Nbox_Shipping::settings';

    /**
     * Execute the save action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Get POST data
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');

        // Here you can save the data (e.g., save to config, or to the database)
        // You might want to add your saving logic here

        // Create redirect result and redirect back to the settings page after saving
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('nbox_shipping/settings/index');
    }
}
