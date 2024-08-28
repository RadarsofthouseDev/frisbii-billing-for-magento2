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
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriptionRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Payment;
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
        Payment $paymentHelper
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
        parent::__construct($context);
    }

    /**
     * Execute
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $this->logger->addDebug(__METHOD__, $params);
        $id = $params['id'] ?? '';
        $isAjax = 0;

        if (isset($params['_isAjax'])) {
            $isAjax = 1;
        }

        if (empty($params['id'])) {
            return null;
        }

        /** @var \Radarsofthouse\BillwerkPlusSubscription\Model\Session $session */
        $session = $this->sessionInterfaceFactory->create()->load($id, 'handle');
        $orderId = $session->getOrderId();

        $order = $this->orderRepository->get($orderId);
        $apiKey = $this->helper->getApiKey($order->getStoreId());

        try {
            $customerSubscriber = $this->customerSubscriberRepository->get($order->getCustomerId());
            $customerSubscriber->setCustomerHandle($params['customer']);
        } catch (NoSuchEntityException $exception) {
            /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber */
            $customerSubscriber = $this->customerSubscriberFactory->create();
            $customerSubscriber->setCustomerHandle($params['customer']);
            $customerSubscriber->setCustomerId(((int)$order->getCustomerId()));
        }

        try {
            $customerSubscriber->setSubscriptionActive(1);
            $this->customerSubscriberRepository->save($customerSubscriber);
        } catch (LocalizedException $e) {
            $this->logger->addDebug($e->getMessage());
        }

        if (isset($params['subscription']) && $session->getOrderType() === 'subscription_order') {
            $metadata = $this->subscriptionHelper->getMetadata($apiKey, $params['subscription']);
            if ($metadata && isset($metadata['magento']['orderId']) && $orderId === $metadata['magento']['orderId']) {
                $order->setBillwerkSubHandle($params['subscription']);
                $order->save();
                /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription */
                $customerSubscription = $this->customerSubscriptionFactory->create();
                $customerSubscription->load($params['subscription'], 'subscription_handle');
                if (!$customerSubscription->getEntityId()) {
                    $customerSubscription->setCustomerHandle($params['customer']);
                    $customerSubscription->setCustomerId($order->getCustomerId());
                    $customerSubscription->setSubscriptionHandle($params['subscription']);
                    $customerSubscription->setOrderId($order->getId());
                    $customerSubscription->setStatus('active');
                    try {
                        $this->customerSubscriptionRepository->save($customerSubscription);
                    } catch (LocalizedException $e) {
                        $this->logger->addDebug($e->getMessage());
                    }
                }
            }
        } elseif ($session->getOrderType() === 'mixed_order') {
            ['plan' => $plan, 'qty' => $qty, 'addOns' => $addOns] =
                $this->paymentHelper->getSubscriptionPlanWithAddons($order);
            ['couponCode' => $couponCode, 'discountHandles' => $discountHandles] =
                $this->paymentHelper->getCouponAndDiscounts($order, $plan, $params['customer']);
            $subscription = [
                'customer' => $params['customer'],
                'plan' => $plan,
                'quantity' => (int)$qty,
                'source' => $params['payment_method'],
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
            $subscription = $this->subscriptionHelper->create($apiKey, $subscription);

            /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription */
            $customerSubscription = $this->customerSubscriptionFactory->create();
            $customerSubscription->load($subscription['handle'], 'subscription_handle');
            if (!$customerSubscription->getEntityId()) {
                $customerSubscription->setCustomerHandle($params['customer']);
                $customerSubscription->setCustomerId($order->getCustomerId());
                $customerSubscription->setSubscriptionHandle($subscription['handle']);
                $customerSubscription->setOrderId($order->getId());
                $customerSubscription->setStatus('active');
                try {
                    $this->customerSubscriptionRepository->save($customerSubscription);
                } catch (LocalizedException $e) {
                    $this->logger->addDebug($e->getMessage());
                }
            }

            $order->setBillwerkSubHandle($subscription['handle']);
            $order->save();
        }

        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());

        if ($isAjax === 1) {
            $result = [];
            $result['status'] = 'success';
            $result['redirect_url'] = $this->url->getUrl('checkout/onepage/success');
            return $this->resultJsonFactory->create()->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0',
                true
            )->setData($result);
        }
        $this->logger->addDebug('Redirect to checkout/onepage/success');
        return $this->redirect('checkout/onepage/success');
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
