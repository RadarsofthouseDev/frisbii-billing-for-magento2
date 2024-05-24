<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Exception;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\SessionRepositoryInterface;

class Payment extends AbstractHelper
{
    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Session
     */
    protected $sessionHelper;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Customer
     */
    private $customerHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerSubscriberRepositoryInterface
     */
    protected $customerSubscriberRepository;

    /**
     * @var SessionInterfaceFactory
     */
    protected $paymentSessionFactory;

    /**
     * @var SessionRepositoryInterface
     */
    protected $paymentSessionRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var string[]
     */
    protected $localMapping = [
        'da_DK' => 'da_DK',
        'sv_SE' => 'sv_SE',
        'nb_NO' => 'no_NO',
        'nn_NO' => 'no_NO',
        'en_AU' => 'en_GB',
        'en_CA' => 'en_GB',
        'en_IE' => 'en_GB',
        'en_NZ' => 'en_GB',
        'en_GB' => 'en_GB',
        'en_US' => 'en_GB',
        'de_AT' => 'de_DE',
        'de_DE' => 'de_DE',
        'de_CH' => 'de_DE',
        'fr_CA' => 'fr_FR',
        'fr_FR' => 'fr_FR',
        'es_AR' => 'es_ES',
        'es_CL' => 'es_ES',
        'es_CO' => 'es_ES',
        'es_CR' => 'es_ES',
        'es_MX' => 'es_ES',
        'es_PA' => 'es_ES',
        'es_PE' => 'es_ES',
        'es_ES' => 'es_ES',
        'es_VE' => 'es_ES',
        'nl_NL' => 'nl_NL',
        'pl_PL' => 'pl_PL',
    ];

    /**
     * @param Context $context
     * @param Resolver $resolver
     * @param UrlInterface $urlInterface
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param Session $sessionHelper
     * @param Customer $customerHelper
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param SessionInterfaceFactory $paymentSessionFactory
     * @param SessionRepositoryInterface $paymentSessionRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context                               $context,
        Resolver                              $resolver,
        UrlInterface                          $urlInterface,
        CustomerSession                       $customerSession,
        StoreManagerInterface                 $storeManager,
        Data                                  $helper,
        Session                               $sessionHelper,
        Customer                              $customerHelper,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        SessionInterfaceFactory               $paymentSessionFactory,
        SessionRepositoryInterface            $paymentSessionRepository,
        ProductRepositoryInterface            $productRepository
    ) {
        parent::__construct($context);
        $this->resolver = $resolver;
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->sessionHelper = $sessionHelper;
        $this->customerHelper = $customerHelper;
        $this->customerSubscriberRepository = $customerSubscriberRepository;
        $this->paymentSessionFactory = $paymentSessionFactory;
        $this->paymentSessionRepository = $paymentSessionRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Map locale code
     *
     * @param string $code
     * @return string
     */
    public function getLocale($code)
    {
        return $this->localMapping[$code] ?? '';
    }

    /**
     * Create checkout session
     *
     * @param Order $order
     * @return string|mixed
     * @throws Exception|LocalizedException|GuzzleException|NoSuchEntityException
     */
    public function createCheckoutSession($order)
    {
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $customerId = $order->getCustomerId();
        if ($customerId) {
            try {
                $customerSubscriber = $this->customerSubscriberRepository->get($customerId);
                $customerHandle = $customerSubscriber->getCustomerHandle();
            } catch (LocalizedException $e) {
                $customerHandle = null;
            }
        }
        if (!$customerHandle) {
            $customerEmail = $order->getCustomerEmail();
            $customerHandle = $this->customerHelper->search($apiKey, $customerEmail);
        }

        $customer = $this->helper->getCustomerData($order);
        $billingAddress = $this->helper->getOrderBillingAddress($order);
        $shippingAddress = $this->helper->getOrderShippingAddress($order);
        $paymentMethods = $this->helper->getPaymentMethods($order);

        $orderData = [
            'handle' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
        ];

        if ($this->helper->getConfig('send_order_line') == '1') {
            $orderData['order_lines'] = $this->helper->getOrderLines($order);
        } else {
            $orderData['order_lines'] = $this->helper->getOrderLines($order);
            $grandTotal = 0;
            foreach ($orderData['order_lines'] as $orderLine) {
                $grandTotal += $orderLine['amount'];
            }
            $orderData['amount'] = $this->helper->toInt($grandTotal);
        }

        $settle = false;
        $autoCaptureConfig = $this->helper->getConfig('auto_capture', $order->getStoreId());
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        if ($autoCaptureConfig == 1) {
            $settle = true;
        }

        $options = [];

        $store = $order->getStore();
        $localeCode = $store->getConfig('general/locale/code');
        if (!empty($this->localMapping[$localeCode])) {
            $options['locale'] = $this->localMapping[$localeCode];
        }

        $baseUrl = $this->storeManager->getStore($order->getStoreId())->getBaseUrl();
        $options['accept_url'] = $baseUrl . 'billwerkplussubscription/standard/accept';
        $options['cancel_url'] = $baseUrl . 'billwerkplussubscription/standard/cancel';
        $options['recurring_optional'] = true;

        $orderData['metadata'] = [
            'magento' => [
                'module' => 'subscription',
                'orderId' => $order->getId(),
                'orderIncrementId' => $order->getIncrementId(),
                'customerId' => $order->getCustomerId(),
            ]
        ];

        if ($customerHandle !== false) {
            $res = $this->sessionHelper->chargeCreateWithExistCustomer(
                $apiKey,
                $customerHandle,
                $orderData,
                $paymentMethods,
                $settle,
                $options
            );
        } else {
            $res = $this->sessionHelper->chargeCreateWithNewCustomer(
                $apiKey,
                $customer,
                $orderData,
                $paymentMethods,
                $settle,
                $options
            );
        }

        if (is_array($res) && isset($res['id'])) {
            $paymentTransactionId = $res['id'];
            try {
                /** @var SessionInterface $paymentSession */
                $paymentSession = $this->paymentSessionFactory->create();
                $paymentSession->setHandle($paymentTransactionId);
                $paymentSession->setOrderType('mixed_order');
                $paymentSession->setOrderId($order->getId());
                $paymentSession->setOrderIncrementId($order->getIncrementId());
                $paymentSession->setCreated(date('Y-m-d H:i:s'));
                $this->paymentSessionRepository->save($paymentSession);
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                return $paymentTransactionId;
            }
            return $paymentTransactionId;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot create Billwerk+ session.')
            );
        }
    }

    /**
     * Create subscription session
     *
     * @param Order $order
     * @return string|mixed
     * @throws Exception|GuzzleException|NoSuchEntityException
     */
    public function createSubscriptionSession($order)
    {
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $customerId = $order->getCustomerId();
        $customerHandle = null;
        if ($customerId) {
            try {
                $customerSubscriber = $this->customerSubscriberRepository->get($customerId);
                $customerHandle = $customerSubscriber->getCustomerHandle();
            } catch (LocalizedException $e) {
                $customerHandle = null;
            }
        }
        if (!$customerHandle) {
            $customerEmail = $order->getCustomerEmail();
            $customerHandle = $this->customerHelper->search($apiKey, $customerEmail);
        }

        $customer = $this->helper->getCustomerData($order);
        $billingAddress = $this->helper->getOrderBillingAddress($order);
        $shippingAddress = $this->helper->getOrderShippingAddress($order);
        $paymentMethods = $this->helper->getPaymentMethods($order);
        $plan = null;
        $qty = 1;

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                if ($subEnabled && !empty($subPlan)) {
                    $plan = $subPlan;
                    $qty = $item->getQtyOrdered();
                }
            } catch (NoSuchEntityException $exception) {
                continue;
            }
        }
        $metadata = [
            'magento' => [
                'module' => 'subscription',
                'orderId' => $order->getId(),
                'orderIncrementId' => $order->getIncrementId(),
                'customerId' => $order->getCustomerId(),
            ]
        ];

        $subscriptionData = [
            'plan' => $plan,
            'quantity' => (int)$qty,
            'generate_handle' => true,
            'metadata' => $metadata
        ];

        $options = [];

        $store = $order->getStore();
        $localeCode = $store->getConfig('general/locale/code');
        if (!empty($this->localMapping[$localeCode])) {
            $options['locale'] = $this->localMapping[$localeCode];
        }

        $baseUrl = $this->storeManager->getStore($order->getStoreId())->getBaseUrl();
        $options['accept_url'] = $baseUrl . 'billwerkplussubscription/standard/accept';
        $options['cancel_url'] = $baseUrl . 'billwerkplussubscription/standard/cancel';
        $options['recurring_optional'] = true;

        $res = false;
        if ($customerHandle !== false) {
            $res = $this->sessionHelper->subscriptionCreateWithExistCustomer(
                $apiKey,
                $customerHandle,
                $subscriptionData,
                $paymentMethods,
                $options
            );
        } else {
            $res = $this->sessionHelper->subscriptionCreateWithNewCustomer(
                $apiKey,
                $customer,
                $subscriptionData,
                $paymentMethods,
                $options
            );
        }

        if (is_array($res) && isset($res['id'])) {
            $paymentTransactionId = $res['id'];
            try {
                /** @var SessionInterface $paymentSession */
                $paymentSession = $this->paymentSessionFactory->create();
                $paymentSession->setHandle($paymentTransactionId);
                $paymentSession->setOrderType('subscription_order');
                $paymentSession->setOrderId($order->getId());
                $paymentSession->setOrderIncrementId($order->getIncrementId());
                $paymentSession->setCreated(date('Y-m-d H:i:s'));
                $this->paymentSessionRepository->save($paymentSession);
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                return $paymentTransactionId;
            }
            return $paymentTransactionId;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot create Billwerk+ session.')
            );
        }
    }
}
