<?php
namespace Nbox\Shipping\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Index action for the Nbox Shipping settings page.
 */
class Index extends Action
{
    /**
     * The admin resource for Nbox Shipping settings.
     *
     * @var string
     */
    public const ADMIN_RESOURCE = 'Nbox_Shipping::settings'; // Explicitly defined as public

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute the index action.
     *
     * @return \Magento\Framework\Controller\Result\Page
     */
    public function execute()
    {
        // Create the result page and set the menu and title
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Nbox_Shipping::settings');
        $resultPage->getConfig()->getTitle()->prepend(__('Nbox Shipping Settings'));
        
        return $resultPage;
    }
}
