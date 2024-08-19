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
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;
use Throwable;

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
     */
    public function __construct(
        Context                                 $context,
        Http                                    $request,
        JsonFactory                             $resultJsonFactory,
        RedirectFactory                         $resultRedirectFactory,
        Data                                    $helper,
        Subscription                            $subscriptionHelper,
        Logger                                  $logger,
        CheckoutSession                         $checkoutSession,
        SessionInterfaceFactory                 $sessionInterfaceFactory,
        OrderRepositoryInterface                $orderRepository,
        ProductRepositoryInterface              $productRepository,
        CustomerSubscriberInterfaceFactory      $customerSubscriberFactory,
        CustomerSubscriptionInterfaceFactory    $customerSubscriptionFactory,
        CustomerSubscriberRepositoryInterface   $customerSubscriberRepository,
        CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository
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
            return;
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
            $plan = null;
            $qty = 1;
            $addOns = null;
            /** @var \Magento\Sales\Model\Order\Item $item */
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                try {
                    if (in_array($item->getProductType(), ['simple', 'virtual'])) {
                        $product = $this->productRepository->getById($item->getProductId());
                        $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                        $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                        $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                        $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                        if ($subEnabled && !empty($subPlan)) {
                            $plan = $subPlan;
                            $qty = $item->getQtyOrdered();
                            $buyRequest = $item->getBuyRequest();
                            $options = $buyRequest->getData('options');
                            if($options){
                                $this->getAddons($order, $item, $options, $addOns);
                            }
                        }
                    } elseif ($item->getProductType() === 'configurable') {
                        /** @var \Magento\Sales\Model\Order\Item[] $childItems */
                        $childItems = $item->getChildrenItems();
                        if ($childItems) {
                            foreach ($childItems as $child) {
                                $product = $this->productRepository->getById($child->getProductId());
                                $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                                $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                                $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                                $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                                if ($subEnabled && !empty($subPlan)) {
                                    $plan = $subPlan;
                                    $qty = $child->getQtyOrdered();
                                    $buyRequest = $item->getBuyRequest();
                                    $options = $buyRequest->getData('options');
                                    if ($options) {
                                        $this->getAddons($order, $item, $options, $addOns);
                                    }
                                }
                            }
                        }
                    }


                } catch (NoSuchEntityException $exception) {
                    continue;
                }
            }
            $subscription = [
                'customer' => $params['customer'],
                'plan' => $plan,
                'quantity' => (int)$qty,
                'source' => $params['payment_method'],
                'signup_method' => 'source',
                'generate_handle' => true,
                'metadata' => [
                    'magento' => [
                        'orderId' => $order->getId(),
                        'orderIncrementId' => $order->getIncrementId(),
                        'customerId' => $order->getCustomerId(),
                    ]
                ]
            ];
            if($addOns){
                $subscription['add_ons'] = $addOns;
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

    private function getAddons($order, $item, $options, &$addOns)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $productCustomOptionRepository */
        $productCustomOptionRepository = $objectManager->get(\Magento\Catalog\Api\ProductCustomOptionRepositoryInterface::class);
        foreach ($options as $index => $option) {
            $productOption = $productCustomOptionRepository->get($item->getProduct()->getSku(), $index);
            $optionValues = $productOption->getValues();
            if (is_array($option)) {
                foreach ($option as $value) {
                    if (isset($optionValues[$value])) {
                        $selectOption = $optionValues[$value];
                        if ($selectOption->getBillwerkAddonHandle()) {
                            $addOns[] = [
                                'handle' => $order->getIncrementId() . '_' . $selectOption->getBillwerkAddonHandle(),
                                'add_on' => $selectOption->getBillwerkAddonHandle(),
                            ];
                        }
                    }
                }
            } else if (strpos($option, ',')) {
                $optionExplode = explode(',', $option);
                foreach ($optionExplode as $value) {
                    if (isset($optionValues[$value])) {
                        $selectOption = $optionValues[$value];
                        if ($selectOption->getBillwerkAddonHandle()) {
                            $addOns[] = [
                                'handle' => $order->getIncrementId() . '_' . $selectOption->getBillwerkAddonHandle(),
                                'add_on' => $selectOption->getBillwerkAddonHandle(),
                            ];
                        }
                    }
                }
            } else {
                if (isset($optionValues[$option])) {
                    $selectOption = $optionValues[$option];
                    if ($selectOption->getBillwerkAddonHandle()) {
                        $addOns[] = [
                            'handle' => $order->getIncrementId() . '_' . $selectOption->getBillwerkAddonHandle(),
                            'add_on' => $selectOption->getBillwerkAddonHandle(),
                        ];
                    }
                }
            }
        }
    }
}
