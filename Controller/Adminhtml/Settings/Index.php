<?php
namespace NBOX\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    const ADMIN_RESOURCE = 'NBOX_Shipping::settings';

    protected $resultPageFactory;
    protected $logger;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;        
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('NBOX_Shipping::settings');
        $resultPage->getConfig()->getTitle()->prepend(__('NBOX Shipping Settings'));
        return $resultPage;
    }
}
