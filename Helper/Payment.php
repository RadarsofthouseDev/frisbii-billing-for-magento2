<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\SessionRepositoryInterface;
use Throwable;

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
     * @var ProductCustomOptionRepositoryInterface
     */
    protected $productCustomOptionRepository;

    /**
     * @var Discount
     */
    protected $discountHelper;

    /**
     * @var Coupon
     */
    protected $couponHelper;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

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
     * @var Logger
     */
    protected $logger;

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
     * @param ProductCustomOptionRepositoryInterface $productCustomOptionRepository
     * @param Discount $discountHelper
     * @param Coupon $couponHelper
     * @param RuleRepositoryInterface $ruleRepository
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Resolver $resolver,
        UrlInterface $urlInterface,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        Data $helper,
        Session $sessionHelper,
        Customer $customerHelper,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        SessionInterfaceFactory $paymentSessionFactory,
        SessionRepositoryInterface $paymentSessionRepository,
        ProductRepositoryInterface $productRepository,
        ProductCustomOptionRepositoryInterface $productCustomOptionRepository,
        Discount $discountHelper,
        Coupon $couponHelper,
        RuleRepositoryInterface $ruleRepository,
        Logger $logger
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
        $this->productCustomOptionRepository = $productCustomOptionRepository;
        $this->discountHelper = $discountHelper;
        $this->couponHelper = $couponHelper;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
    }

    /**
     * Map locale code
     *
     * @param string $code
     * @return string
     */
    public function getLocale(string $code): string
    {
        return $this->localMapping[$code] ?? '';
    }

    /**
     * Get Billwerk customer handle from order.
     *
     * @param OrderInterface|Order $order
     * @return false|string|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getBillwerkCustomerHandle($order)
    {
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $customerId = $order->getCustomerId();
        $customerHandle = null;
        if ($customerId) {
            try {
                $customerSubscriber = $this->customerSubscriberRepository->get($customerId);
                $customerHandle = $customerSubscriber->getCustomerHandle();
                $customer = $this->customerHelper->get($apiKey, $customerHandle);
                if (false === $customer) {
                    $customerHandle = null;
                }
            } catch (LocalizedException | GuzzleException $e) {
                $this->_logger->info($e->getMessage());
            }
        }
        if (!$customerHandle) {
            $customerEmail = $order->getCustomerEmail();
            $customerHandle = $this->customerHelper->search($apiKey, $customerEmail);
        }

        return $customerHandle;
    }

    /**
     * Prepare default options
     *
     * @param OrderInterface|Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function defaultOption($order): array
    {
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

        return $options;
    }

    /**
     * Create checkout session
     *
     * @param OrderInterface|Order $order
     * @return string|mixed
     * @throws Exception|LocalizedException|GuzzleException|NoSuchEntityException
     */
    public function createCheckoutSession($order)
    {
        $this->logger->addInfo(__METHOD__ . ' Creating checkout session for order id ' . $order->getId());
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $customerHandle = $this->getBillwerkCustomerHandle($order);
        $billingAddress = $this->helper->getOrderBillingAddress($order);
        $shippingAddress = $this->helper->getOrderShippingAddress($order);
        $paymentMethods = $this->helper->getPaymentMethods($order);

        $orderData = [
            'handle' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
        ];

        $orderLines = $this->helper->getOrderLines($order);
        if ($this->helper->getConfig('send_order_line') == '1') {
            $orderData['order_lines'] = $orderLines;
        } else {
            $grandTotal = 0;
            foreach ($orderLines as $orderLine) {
                $grandTotal += $orderLine['amount'];
            }
            $orderData['amount'] = $this->helper->toInt($grandTotal);
        }

        $settle = $this->helper->getConfig('auto_capture', $order->getStoreId()) == 1;
        $options = $this->defaultOption($order);

        $orderData['metadata'] = [
            'magento' => [
                'module' => 'subscription',
                'orderId' => $order->getId(),
                'orderType' => 'Mixed',
                'orderIncrementId' => $order->getIncrementId(),
                'customerId' => $order->getCustomerId(),
            ]
        ];

        $ageVerification = $this->helper->getAgeVerification($order);
        if ($ageVerification !== false) {
            $options['minimum_user_age'] = (int)$ageVerification;
            $options['session_data'] = [
                'mpo_minimum_user_age' => (int)$ageVerification,
                'vipps_epayment_minimum_user_age' => (int)$ageVerification
            ];
        }

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
            $customer = $this->helper->getCustomerData($order);
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
            $this->logger->addInfo(__METHOD__ . ' Created checkout session for order id ' . $order->getId());
            try {
                /** @var SessionInterface $paymentSession */
                $paymentSession = $this->paymentSessionFactory->create();
                $paymentSession->setHandle($paymentTransactionId);
                $paymentSession->setOrderType('mixed_order');
                $paymentSession->setOrderId($order->getId());
                $paymentSession->setOrderIncrementId($order->getIncrementId());
                $paymentSession->setCreated(date('Y-m-d H:i:s'));
                $this->paymentSessionRepository->save($paymentSession);
            } catch (LocalizedException $exception) {
                return $paymentTransactionId;
            }
            return $paymentTransactionId;
        } else {
            $this->logger->addError(__METHOD__ . ' Unable to create checkout session for order id ' . $order->getId());
            throw new LocalizedException(
                __('Cannot create Frisbii session.')
            );
        }
    }

    /**
     * Get addon from subscription item
     *
     * @param OrderInterface|Order $order
     * @param OrderItemInterface $item
     * @param array $options
     * @param array $addOns
     * @return void
     */
    public function getAddons($order, $item, $options, &$addOns)
    {
        foreach ($options as $index => $option) {
            $productOption = $this->productCustomOptionRepository->get($item->getProduct()->getSku(), $index);
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
            } elseif (strpos($option, ',') !== false) {
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

    /**
     * Get subscription plan and addons from order.
     *
     * @param OrderInterface|Order $order
     * @return array
     */
    public function getSubscriptionPlanWithAddons($order): array
    {
        $plan = null;
        $qty = 1;
        $addOns = null;

        /** @var OrderItemInterface[] $orderItems */
        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $item) {
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
                        if ($options) {
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

        return compact('plan', 'qty', 'addOns');
    }

    /**
     * Get coupon code and discounts from order.
     *
     * @param OrderInterface|Order $order
     * @param string $plan
     * @param null|string $customerHandle
     * @return array
     */
    public function getCouponAndDiscounts($order, $plan, $customerHandle = null): array
    {
        try {
            $apiKey = $this->helper->getApiKey($order->getStoreId());
        } catch (NoSuchEntityException $e) {
            $apiKey = null;
        }
        $discountHandles = [];
        $couponCode = $order->getCouponCode();
        if (!empty($couponCode)) {
            $options = ['plan' => $plan];
            if ($customerHandle) {
                $options['customer'] = $customerHandle;
            }
            try {
                if (!$this->couponHelper->validate($apiKey, $couponCode, $options)) {
                    $couponCode = null;
                }
            } catch (Throwable $exception) {
                $couponCode = null;
            }
        }
        $appliedRuleIds = $order->getAppliedRuleIds();
        if (!empty($appliedRuleIds)) {
            $salesRulesIds = explode(',', $appliedRuleIds);
            if (count($salesRulesIds)) {
                foreach ($salesRulesIds as $salesRuleId) {
                    try {
                        $salesRule = $this->ruleRepository->getById($salesRuleId);
                        $salesRuleData = $salesRule->__toArray();
                        if (
                            array_key_exists('coupon_code', $salesRuleData)
                            && null !== $salesRuleData['coupon_code']
                            && $couponCode === $salesRuleData['coupon_code']
                        ) {
                            continue;
                        }
                        if (
                            array_key_exists('billwerk_discount_handle', $salesRuleData)
                            && empty($salesRuleData['billwerk_discount_handle'])
                        ) {
                            continue;
                        }
                        $discountHandle = $salesRuleData['billwerk_discount_handle'];
                        try {
                            $discount = $this->discountHelper->get($apiKey, $discountHandle);
                        } catch (Throwable $exception) {
                            $discount = false;
                        }
                        if ($discount && array_key_exists('state', $discount) && $discount['state'] == 'active') {
                            $discountHandles[] = $discountHandle;
                        }
                    } catch (LocalizedException $exception) {
                        continue;
                    }
                }
            }
        }
        return compact('couponCode', 'discountHandles');
    }

    /**
     * Create subscription session
     *
     * @param OrderInterface|Order $order
     * @return string|mixed
     * @throws Exception|GuzzleException|NoSuchEntityException
     */
    public function createSubscriptionSession($order)
    {
        $this->logger->addInfo(__METHOD__ . ' creating subscription session for order id ' . $order->getId());
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $customerHandle = $this->getBillwerkCustomerHandle($order);
        $paymentMethods = $this->helper->getPaymentMethods($order);
        ['plan' => $plan, 'qty' => $qty, 'addOns' => $addOns] =
            $this->getSubscriptionPlanWithAddons($order);
        ['couponCode' => $couponCode, 'discountHandles' => $discountHandles] =
            $this->getCouponAndDiscounts($order, $plan, $customerHandle);

        $metadata = [
            'magento' => [
                'module' => 'subscription',
                'orderId' => $order->getId(),
                'orderType' => 'Subscription',
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

        if ($addOns) {
            $subscriptionData['add_ons'] = $addOns;
        }

        if (!empty($couponCode)) {
            $subscriptionData['coupon_codes'] = [$couponCode];
        }

        if (!empty($discountHandles)) {
            foreach ($discountHandles as $discountHandle) {
                $subscriptionData['subscription_discounts'][] = [
                    'handle' => "{$order->getIncrementId()}_$discountHandle",
                    'discount' => $discountHandle,
                ];
            }
        }

        $options = $this->defaultOption($order);

        if ($this->helper->getConfig('api_key_type', $order->getStoreId()) == '0') {
            $subscriptionData['test'] = true;
        }

        $ageVerification = $this->helper->getAgeVerification($order);
        if ($ageVerification !== false) {
            $options['minimum_user_age'] = (int)$ageVerification;
            $options['session_data'] = [
                'mpo_minimum_user_age' => (int)$ageVerification,
                'vipps_epayment_minimum_user_age' => (int)$ageVerification
            ];
        }

        if ($customerHandle !== false) {
            $res = $this->sessionHelper->subscriptionCreateWithExistCustomer(
                $apiKey,
                $customerHandle,
                $subscriptionData,
                $paymentMethods,
                $options
            );
        } else {
            $customer = $this->helper->getCustomerData($order);
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
            $this->logger->addInfo(__METHOD__ . ' created subscription session for order id ' . $order->getId());
            try {
                /** @var SessionInterface $paymentSession */
                $paymentSession = $this->paymentSessionFactory->create();
                $paymentSession->setHandle($paymentTransactionId);
                $paymentSession->setOrderType('subscription_order');
                $paymentSession->setOrderId($order->getId());
                $paymentSession->setOrderIncrementId($order->getIncrementId());
                $paymentSession->setCreated(date('Y-m-d H:i:s'));
                $this->paymentSessionRepository->save($paymentSession);
            } catch (LocalizedException $exception) {
                return $paymentTransactionId;
            }
            return $paymentTransactionId;
        } else {
            $this->logger->addError(__METHOD__ . ' Unable to create subscription session for order id '
                . $order->getId());
            throw new LocalizedException(
                __('Cannot create Frisbii session.')
            );
        }
    }
}
