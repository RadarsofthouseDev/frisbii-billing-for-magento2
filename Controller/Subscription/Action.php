<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Subscription;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriptionRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface;
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
     * @var CustomerSubscriptionRepositoryInterface
     */
    protected $customerSubscriptionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

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
     * @param CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
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
        Payment               $paymentHelper,
        CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->sessionHelper = $sessionHelper;
        $this->paymentHelper = $paymentHelper;
        $this->customerSubscriptionRepository = $customerSubscriptionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
    }

    /**
     * Check that the current customer owns the submitted subscription.
     *
     * @param string $handle
     * @return bool
     */
    private function customerOwnsSubscription(string $handle): bool
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if ($customerId <= 0) {
            return false;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(CustomerSubscriptionInterface::SUBSCRIPTION_HANDLE, $handle)
            ->addFilter(CustomerSubscriptionInterface::CUSTOMER_ID, $customerId)
            ->create();

        return $this->customerSubscriptionRepository->getList($searchCriteria)->getTotalCount() > 0;
    }

    /**
     * Check that the merchant allows the customer to run the action (self-service settings).
     *
     * @param string $action
     * @return bool
     */
    private function isActionEnabled(string $action): bool
    {
        switch ($action) {
            case 'on_hold':
            case 'reactivate':
                return (bool)$this->helper->getConfig('enable_self_pause');
            case 'cancel':
            case 'uncancel':
                return (bool)$this->helper->getConfig('enable_self_cancel');
            default:
                return true;
        }
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
        $action = $params['action'] ?? null;
        $handle = $params['handle'] ?? null;
        // "expire" is not a customer action (no UI), so it is not allowed here
        $allowedActions = ['on_hold', 'reactivate', 'cancel', 'uncancel', 'add_payment'];
        if (
            !is_string($action)
            || !is_string($handle)
            || $handle === ''
            || !in_array($action, $allowedActions, true)
            || !$this->customerOwnsSubscription($handle)
        ) {
            $this->messageManager->addErrorMessage(__('The subscription could not be found.'));
            $resultRedirect->setPath('billwerkplussubscription/customer/subscriptions');
            return $resultRedirect;
        }

        if (!$this->isActionEnabled($action)) {
            // same store settings that hide the buttons in the subscription view
            $this->messageManager->addErrorMessage(__('This action is not available.'));
            $resultRedirect->setPath('billwerkplussubscription/customer/subscriptionview', ['handle' => $handle]);
            return $resultRedirect;
        }

        $apiKey = $this->helper->getApiKey();
        switch ($action) {
            case 'on_hold':
                if ($this->subscriptionHelper->onHold($apiKey, $handle)) {
                    $this->messageManager->addSuccessMessage(__('The subscription has been put on hold.
'));
                } else {
                    $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                }
                break;
            case 'reactivate':
                if ($this->subscriptionHelper->reactivate($apiKey, $handle)) {
                    $this->messageManager
                        ->addSuccessMessage(__('The subscription status has been changed to active.
'));
                } else {
                    $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                }
                break;
            case 'cancel':
                if ($this->subscriptionHelper->cancel($apiKey, $handle)) {
                    $this->messageManager
                        ->addSuccessMessage(__('The subscription has been changed to cancelled.'));
                } else {
                    $this->messageManager->addWarningMessage(__('The subscription has not been changed.'));
                }
                break;
            case 'uncancel':
                if ($this->subscriptionHelper->uncancel($apiKey, $handle)) {
                    $this->messageManager
                        ->addSuccessMessage(__('The subscription status has been changed to active.'));
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
                    'subscription' => $handle,
                    'accept_url' => $baseUrl . $path . $handle,
                    'cancel_url' => $baseUrl . $path . $handle,
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
        $resultRedirect
            ->setPath('billwerkplussubscription/customer/subscriptionview', ['handle' => $handle]);
        return $resultRedirect;
    }
}
