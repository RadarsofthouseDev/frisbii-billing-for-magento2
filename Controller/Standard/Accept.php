<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Standard;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriptionRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Charge;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Payment;
use Radarsofthouse\BillwerkPlusSubscription\Helper\PaymentMethod;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;

class Accept extends Action
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var SessionInterfaceFactory
     */
    protected $sessionInterfaceFactory;

    /**
     * @var Subscription
     */
    protected $subscriptionHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CustomerSubscriberInterfaceFactory
     */
    protected $customerSubscriberFactory;

    /**
     * @var CustomerSubscriptionInterfaceFactory
     */
    protected $customerSubscriptionFactory;

    /**
     * @var CustomerSubscriberRepositoryInterface
     */
    protected $customerSubscriberRepository;

    /**
     * @var CustomerSubscriptionRepositoryInterface
     */
    protected $customerSubscriptionRepository;
    /**
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * @var Charge
     */
    protected $chargeHelper;

    /**
     * @var PaymentMethod
     */
    protected $paymentMethodHelper;

    /**
     * @var LockManagerInterface
     */
    protected $lockManager;

    /**
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param Subscription $subscriptionHelper
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param SessionInterfaceFactory $sessionInterfaceFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerSubscriberInterfaceFactory $customerSubscriberFactory
     * @param CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository
     * @param Payment $paymentHelper
     * @param Charge $chargeHelper
     * @param PaymentMethod $paymentMethodHelper
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        Data $helper,
        Subscription $subscriptionHelper,
        Logger $logger,
        CheckoutSession $checkoutSession,
        SessionInterfaceFactory $sessionInterfaceFactory,
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        CustomerSubscriberInterfaceFactory $customerSubscriberFactory,
        CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository,
        Payment $paymentHelper,
        Charge $chargeHelper,
        PaymentMethod $paymentMethodHelper,
        LockManagerInterface $lockManager
    ) {
        $this->request = $request;
        $this->url = $context->getUrl();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->sessionInterfaceFactory = $sessionInterfaceFactory;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->customerSubscriberFactory = $customerSubscriberFactory;
        $this->customerSubscriptionFactory = $customerSubscriptionFactory;
        $this->customerSubscriberRepository = $customerSubscriberRepository;
        $this->customerSubscriptionRepository = $customerSubscriptionRepository;
        $this->paymentHelper = $paymentHelper;
        $this->chargeHelper = $chargeHelper;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->lockManager = $lockManager;
        parent::__construct($context);
    }

    /**
     * Execute
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $id = isset($params['id']) && is_scalar($params['id']) ? trim((string)$params['id']) : '';
        $invoice = isset($params['invoice']) && is_scalar($params['invoice']) ? trim((string)$params['invoice']) : '';
        $customerParam = isset($params['customer']) && is_scalar($params['customer'])
            ? trim((string)$params['customer'])
            : '';
        $paymentMethodParam = isset($params['payment_method']) && is_scalar($params['payment_method'])
            ? trim((string)$params['payment_method'])
            : '';
        $subscriptionParam = isset($params['subscription']) && is_scalar($params['subscription'])
            ? trim((string)$params['subscription'])
            : '';
        $isAjax = isset($params['_isAjax']) ? 1 : 0;

        $this->logger->addDebug(__METHOD__, $params);

        if ($id === '') {
            return $this->rejectCallback($isAjax);
        }

        /** @var \Radarsofthouse\BillwerkPlusSubscription\Model\Session $session */
        $session = $this->sessionInterfaceFactory->create()->load($id, 'handle');
        $orderId = $session->getOrderId();
        $orderType = (string)$session->getOrderType();
        if (empty($orderId) || !in_array($orderType, ['subscription_order', 'mixed_order'], true)) {
            $this->logger->addError('Rejected accept callback with unknown payment session.');
            return $this->rejectCallback($isAjax);
        }

        $lockName = 'billwerkplus_accept_' . hash('sha256', $id);
        if (!$this->lockManager->lock($lockName, 30)) {
            // another request is already processing this session
            $this->logger->addDebug('Accept callback is already being processed for the session.');
            return $this->successResult($isAjax);
        }

        try {
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $exception) {
                return $this->rejectCallback($isAjax);
            }

            // the session row binds the handle to the order at place order time
            $sessionIncrementId = (string)$session->getOrderIncrementId();
            if ($sessionIncrementId !== '' && $sessionIncrementId !== (string)$order->getIncrementId()) {
                $this->logger->addError('Rejected accept callback: session does not match the order.');
                return $this->rejectCallback($isAjax);
            }

            $apiKey = $this->helper->getApiKey($order->getStoreId());

            if ($orderType === 'subscription_order') {
                $verified = $this->verifySubscriptionOrder($apiKey, $order, $subscriptionParam);
            } else {
                $verified = $this->verifyMixedOrder(
                    $apiKey,
                    $order,
                    $id,
                    $invoice,
                    $customerParam,
                    $paymentMethodParam
                );
            }
            if ($verified === null) {
                return $this->rejectCallback($isAjax);
            }
            // only values confirmed by the provider are used from here on
            $customerHandle = $verified['customer'];

            try {
                $customerSubscriber = $this->customerSubscriberRepository->get($order->getCustomerId());
                $customerSubscriber->setCustomerHandle($customerHandle);
            } catch (NoSuchEntityException $exception) {
                /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber */
                $customerSubscriber = $this->customerSubscriberFactory->create();
                $customerSubscriber->setCustomerHandle($customerHandle);
                $customerSubscriber->setCustomerId(((int)$order->getCustomerId()));
            }

            try {
                $customerSubscriber->setSubscriptionActive(1);
                $this->customerSubscriberRepository->save($customerSubscriber);
            } catch (LocalizedException $e) {
                $this->logger->addDebug($e->getMessage());
            }

            if ($orderType === 'subscription_order') {
                $subscriptionHandle = $verified['subscription'];
                $order->setBillwerkSubHandle($subscriptionHandle);
                $order->save();
                $this->saveCustomerSubscription($order, $customerHandle, $subscriptionHandle);
            } elseif (empty($order->getBillwerkSubHandle())) {
                // mixed order: create the provider subscription only once per order
                $subscriptionHandle = $this->createMixedOrderSubscription(
                    $apiKey,
                    $order,
                    $customerHandle,
                    $verified['payment_method']
                );
                if ($subscriptionHandle === null) {
                    return $this->rejectCallback($isAjax);
                }
                $this->saveCustomerSubscription($order, $customerHandle, $subscriptionHandle);
                $order->setBillwerkSubHandle($subscriptionHandle);
                $order->save();
            } else {
                $this->logger->addDebug('Subscription already created for the order, skip creating a new one.');
            }
        } finally {
            $this->lockManager->unlock($lockName);
        }

        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());

        return $this->successResult($isAjax);
    }

    /**
     * Verify a subscription_order callback with the provider.
     *
     * @param string $apiKey
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $subscriptionHandle
     * @return array|null ['customer' => string, 'subscription' => string] or null when not verified
     */
    private function verifySubscriptionOrder($apiKey, $order, $subscriptionHandle)
    {
        if ($subscriptionHandle === '') {
            $this->logger->addError('Rejected accept callback: missing subscription handle.');
            return null;
        }

        $metadata = $this->subscriptionHelper->getMetadata($apiKey, $subscriptionHandle);
        if (!is_array($metadata)
            || !isset($metadata['magento']['orderId'])
            || (string)$metadata['magento']['orderId'] !== (string)$order->getId()
        ) {
            $this->logger->addError('Rejected accept callback: subscription does not belong to the order.');
            return null;
        }

        $subscription = $this->subscriptionHelper->get($apiKey, $subscriptionHandle);
        if (!is_array($subscription) || empty($subscription['customer'])) {
            $this->logger->addError('Rejected accept callback: unable to read subscription from the provider.');
            return null;
        }

        return ['customer' => (string)$subscription['customer'], 'subscription' => $subscriptionHandle];
    }

    /**
     * Verify a mixed_order callback with the provider.
     *
     * @param string $apiKey
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $sessionHandle
     * @param string $invoice
     * @param string $customerHandle
     * @param string $paymentMethod
     * @return array|null ['customer' => string, 'payment_method' => string] or null when not verified
     */
    private function verifyMixedOrder($apiKey, $order, $sessionHandle, $invoice, $customerHandle, $paymentMethod)
    {
        if ($invoice !== (string)$order->getIncrementId() || $customerHandle === '' || $paymentMethod === '') {
            $this->logger->addError('Rejected accept callback: invalid mixed order parameters.');
            return null;
        }

        try {
            $charge = $this->chargeHelper->get($apiKey, (string)$order->getIncrementId());
            $paymentMethodData = $this->paymentMethodHelper->get($apiKey, $paymentMethod);
        } catch (\Throwable $exception) {
            $this->logger->addError('Unable to verify accept callback with the provider.');
            return null;
        }

        $chargeState = is_array($charge) && isset($charge['state']) ? (string)$charge['state'] : '';
        if (!in_array($chargeState, ['authorized', 'settled'], true)) {
            $this->logger->addError('Rejected accept callback: charge state is ' . ($chargeState ?: 'unknown'));
            return null;
        }

        if (!is_array($paymentMethodData)
            || !isset($paymentMethodData['customer'])
            || (string)$paymentMethodData['customer'] !== $customerHandle
            || (isset($charge['customer']) && (string)$charge['customer'] !== $customerHandle)
            || (!empty($paymentMethodData['reference']) && (string)$paymentMethodData['reference'] !== $sessionHandle)
        ) {
            $this->logger->addError('Rejected accept callback: customer or payment method mismatch.');
            return null;
        }

        return ['customer' => (string)$paymentMethodData['customer'], 'payment_method' => $paymentMethod];
    }

    /**
     * Create the provider subscription of a mixed order.
     *
     * @param string $apiKey
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $customerHandle
     * @param string $paymentMethod
     * @return string|null subscription handle
     */
    private function createMixedOrderSubscription($apiKey, $order, $customerHandle, $paymentMethod)
    {
        ['plan' => $plan, 'qty' => $qty, 'addOns' => $addOns] =
            $this->paymentHelper->getSubscriptionPlanWithAddons($order);
        ['couponCode' => $couponCode, 'discountHandles' => $discountHandles] =
            $this->paymentHelper->getCouponAndDiscounts($order, $plan, $customerHandle);
        $subscription = [
            'customer' => $customerHandle,
            'plan' => $plan,
            'quantity' => (int)$qty,
            'source' => $paymentMethod,
            'signup_method' => 'source',
            'generate_handle' => true,
            'metadata' => [
                'magento' => [
                    'module' => 'subscription',
                    'orderId' => $order->getId(),
                    'orderType' => 'Mixed',
                    'orderIncrementId' => $order->getIncrementId(),
                    'customerId' => $order->getCustomerId(),
                ]
            ]
        ];
        if ($this->helper->getConfig('api_key_type', $order->getStoreId()) == '0') {
            $subscription['test'] = true;
        }
        if ($addOns) {
            $subscription['add_ons'] = $addOns;
        }
        if (!empty($couponCode)) {
            $subscription['coupon_codes'] = [$couponCode];
        }
        if (!empty($discountHandles)) {
            foreach ($discountHandles as $discountHandle) {
                $subscription['subscription_discounts'][] = [
                    'handle' => "{$order->getIncrementId()}_$discountHandle",
                    'discount' => $discountHandle,
                ];
            }
        }
        $this->logger->addDebug(__METHOD__, $subscription);
        $created = $this->subscriptionHelper->create($apiKey, $subscription);
        if (!is_array($created) || empty($created['handle'])) {
            $this->logger->addError('Unable to create the subscription for the mixed order.');
            return null;
        }

        return (string)$created['handle'];
    }

    /**
     * Save the local subscription row when it does not exist yet.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $customerHandle
     * @param string $subscriptionHandle
     * @return void
     */
    private function saveCustomerSubscription($order, $customerHandle, $subscriptionHandle)
    {
        /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription */
        $customerSubscription = $this->customerSubscriptionFactory->create();
        $customerSubscription->load($subscriptionHandle, 'subscription_handle');
        if (!$customerSubscription->getEntityId()) {
            $customerSubscription->setCustomerHandle($customerHandle);
            $customerSubscription->setCustomerId($order->getCustomerId());
            $customerSubscription->setSubscriptionHandle($subscriptionHandle);
            $customerSubscription->setOrderId($order->getId());
            $customerSubscription->setStatus('active');
            try {
                $this->customerSubscriptionRepository->save($customerSubscription);
            } catch (LocalizedException $e) {
                $this->logger->addDebug($e->getMessage());
            }
        }
    }

    /**
     * Success result (JSON for ajax, redirect otherwise).
     *
     * @param int $isAjax
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function successResult($isAjax)
    {
        if ($isAjax === 1) {
            return $this->resultJsonFactory->create()->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0',
                true
            )->setData([
                'status' => 'success',
                'redirect_url' => $this->url->getUrl('checkout/onepage/success'),
            ]);
        }
        $this->logger->addDebug('Redirect to checkout/onepage/success');
        return $this->redirect('checkout/onepage/success');
    }

    /**
     * Reject a callback without revealing why.
     *
     * @param int $isAjax
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function rejectCallback($isAjax)
    {
        if ($isAjax !== 1) {
            return $this->redirect('checkout/cart');
        }

        return $this->resultJsonFactory->create()
            ->setHttpResponseCode(400)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
            ->setData([
                'status' => 'error',
                'message' => 'Invalid payment session.',
            ]);
    }

    /**
     * Redirect
     *
     * @param string $path
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirect($path)
    {
        $resultPage = $this->resultRedirectFactory->create()->setPath($path);
        $resultPage->setHeader(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0',
            true
        );
        return $resultPage;
    }
}
