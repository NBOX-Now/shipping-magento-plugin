<?php
namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'NBOX_Shipping::settings';

    public function execute()
    {
        // Get POST data
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');

        // Here you can save the data (e.g., save to config, or to the database)
        // You might want to add your saving logic here

        // Redirect back to the settings page after saving
        $this->_redirect('nbox_shipping/settings/index');
    }
}
