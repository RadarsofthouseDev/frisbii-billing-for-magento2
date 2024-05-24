<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Subscription;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Payment;
use Radarsofthouse\BillwerkPlusSubscription\Helper\PaymentMethod;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Session;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;

class Action extends \Magento\Framework\App\Action\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Subscription
     */
    protected $subscriptionHelper;

    /**
     * @var PaymentMethod
     */
    protected $paymentMethodHelper;

    /**
     * @var Session
     */
    protected $sessionHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param Subscription $subscriptionHelper
     * @param PaymentMethod $paymentMethodHelper
     * @param Session $sessionHelper
     * @param Payment $paymentHelper
     */
    public function __construct(
        Context               $context,
        PageFactory           $resultPageFactory,
        CustomerSession       $customerSession,
        StoreManagerInterface $storeManager,
        Data                  $helper,
        Subscription          $subscriptionHelper,
        PaymentMethod         $paymentMethodHelper,
        Session               $sessionHelper,
        Payment               $paymentHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->sessionHelper = $sessionHelper;
        $this->paymentHelper = $paymentHelper;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        $params = $this->getRequest()->getPostValue();
        if (array_key_exists('action', $params)) {
            $apiKey = $this->helper->getApiKey();
            switch ($params['action']) {
                case 'on_hold':
                    if ($this->subscriptionHelper->onHold($apiKey, $params['handle'])) {
                        $this->messageManager->addSuccessMessage(__('The subscription has been put on hold.
'));
                    } else {
                        $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                    }
                    break;
                case 'reactivate':
                    if ($this->subscriptionHelper->reactivate($apiKey, $params['handle'])) {
                        $this->messageManager
                            ->addSuccessMessage(__('The subscription status has been changed to active.
'));
                    } else {
                        $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                    }
                    break;
                case 'cancel':
                    if ($this->subscriptionHelper->cancel($apiKey, $params['handle'])) {
                        $this->messageManager
                            ->addSuccessMessage(__('The subscription has been changed to cancelled.'));
                    } else {
                        $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                    }
                    break;
                case 'uncancel':
                    if ($this->subscriptionHelper->uncancel($apiKey, $params['handle'])) {
                        $this->messageManager
                            ->addSuccessMessage(__('The subscription status has been changed to active.'));
                    } else {
                        $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                    }
                    break;
                case 'expire':
                    if ($this->subscriptionHelper->expire($apiKey, $params['handle'])) {
                        $this->messageManager
                            ->addSuccessMessage(__('The subscription has been put on hold.'));
                    } else {
                        $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                    }
                    break;
                case 'add_payment':
                    $localeCode = $this->storeManager->getStore()->getConfig('general/locale/code');
                    $locale = $this->paymentHelper->getLocale($localeCode);
                    $allowedPaymentConfig = $this->helper
                        ->getConfig('allowwed_payment', $this->storeManager->getStore()->getId());
                    $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                    $path = 'billwerkplussubscription/customer/subscriptionview/handle/';
                    $subscription = [
                        'subscription' => $params['handle'],
                        'accept_url' => $baseUrl . $path . $params['handle'],
                        'cancel_url' => $baseUrl . $path . $params['handle'],
                        'payment_methods' => explode(',', $allowedPaymentConfig),
                        'recurring_optional' => true,
                    ];
                    if (!empty($locale)) {
                        $subscription['locale'] = $locale;
                    }
                    $session = $this->sessionHelper->subscriptionCreate($apiKey, $subscription);
                    if ($session && array_key_exists('url', $session)) {
                        $resultRedirect->setUrl($session['url']);
                        return $resultRedirect;
                    }
                    break;
            }
        }
        $resultRedirect
            ->setPath('billwerkplussubscription/customer/subscriptionview', ['handle' => $params['handle']]);
        return $resultRedirect;
    }
}
