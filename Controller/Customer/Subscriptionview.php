<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Subscriptionview extends Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Http
     */
    protected $request;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        $handle = $this->request->getParam('handle');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Subscription #'.$handle);
        return $resultPage;
    }
}
