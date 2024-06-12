<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Model\StatusFactory;
use Magento\Catalog\Model\Product;

class Data extends AbstractHelper
{
    public const CONFIG_PATH = 'payment/billwerkplus_subscription/';

    public const PAYMENT_METHODS = [
        'billwerkplus_subscription',
    ];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var StatusFactory
     */
    protected $orderStatusFactory;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param BuilderInterface $transactionBuilder
     * @param PriceHelper $priceHelper
     * @param StatusFactory $orderStatusFactory
     */
    public function __construct(
        Context                    $context,
        ScopeConfigInterface       $scopeConfig,
        StoreManagerInterface      $storeManager,
        ProductRepositoryInterface $productRepository,
        BuilderInterface           $transactionBuilder,
        PriceHelper                $priceHelper,
        StatusFactory              $orderStatusFactory
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->priceHelper = $priceHelper;
        $this->orderStatusFactory = $orderStatusFactory;
    }

    /**
     * Get module configuration
     *
     * @param string $code
     * @param int|string|null $storeId
     * @return mixed
     */
    public function getConfig(string $code, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get private api key from backend configuration
     *
     * @param integer|null $storeId
     * @return string $apiKey
     * @throws NoSuchEntityException
     */
    public function getApiKey($storeId = null)
    {

        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $testModeConfig = $this->getConfig('api_key_type', $storeId);
        if ($testModeConfig == 1) {
            $apiKey = $this->getConfig('private_key', $storeId);
        } else {
            $apiKey = $this->getConfig('private_key_test', $storeId);
        }
        return $apiKey ?? '';
    }

    /**
     * Get customer data from order
     *
     * @param Order $order
     * @return array (customer data)
     */
    public function getCustomerData($order)
    {
        $testModeConfig = $this->getConfig('api_key_type', $order->getStoreId());
        $testMode = true;
        if ($testModeConfig == 1) {
            $testMode = false;
        }

        $address1 = $order->getBillingAddress()->getStreet(1);
        $address2 = $order->getBillingAddress()->getStreet(2);

        $vatId = '';
        if (!empty($order->getBillingAddress()->getVatId())) {
            $vatId = $order->getBillingAddress()->getVatId();
        }

        return [
            'email' => $order->getBillingAddress()->getEmail(),
            'first_name' => $order->getBillingAddress()->getFirstname(),
            'last_name' => $order->getBillingAddress()->getLastname(),
            'address' => $address1[0],
            'address2' => $address2[0],
            'city' => $order->getBillingAddress()->getCity(),
            'country' => $order->getBillingAddress()->getCountryId(),
            'phone' => $order->getBillingAddress()->getTelephone(),
            'company' => $order->getBillingAddress()->getCompany(),
            'postal_code' => $order->getBillingAddress()->getPostcode(),
            'vat' => $vatId,
            'test' => $testMode,
            'generate_handle' => true,
        ];
    }

    /**
     * Get billing address from order
     *
     * @param Order $order
     * @return array (billing address data)
     */
    public function getOrderBillingAddress($order)
    {
        if (null !== $order->getBillingAddress()) {
            $address1 = $order->getBillingAddress()->getStreetLine(1);
            $address2 = $order->getBillingAddress()->getStreetLine(2);

            $vatId = '';
            if (!empty($order->getBillingAddress()->getVatId())) {
                $vatId = $order->getBillingAddress()->getVatId();
            }

            return [
                'company' => $order->getBillingAddress()->getCompany(),
                'vat' => $vatId,
                'attention' => '',
                'address' => $address1,
                'address2' => $address2,
                'city' => $order->getBillingAddress()->getCity(),
                'country' => $order->getBillingAddress()->getCountryId(),
                'email' => $order->getBillingAddress()->getEmail(),
                'phone' => $order->getBillingAddress()->getTelephone(),
                'first_name' => $order->getBillingAddress()->getFirstname(),
                'last_name' => $order->getBillingAddress()->getLastname(),
                'postal_code' => $order->getBillingAddress()->getPostcode(),
                'state_or_province' => $order->getBillingAddress()->getRegion(),
            ];
        }
        return [
            'company' => '',
            'vat' => '',
            'attention' => '',
            'address' => '',
            'address2' => '',
            'city' => '',
            'country' => '',
            'email' => '',
            'phone' => '',
            'first_name' => '',
            'last_name' => '',
            'postal_code' => '',
            'state_or_province' => '',
        ];
    }

    /**
     * Get shipping address from order
     *
     * @param Order $order
     * @return array (shipping address data)
     */
    public function getOrderShippingAddress($order)
    {
        if (null === $order->getShippingAddress()) {
            return $this->getOrderBillingAddress($order);
        }

        $address1 = $order->getShippingAddress()->getStreetLine(1);
        $address2 = $order->getShippingAddress()->getStreetLine(2);

        $vatId = '';
        if (!empty($order->getShippingAddress()->getVatId())) {
            $vatId = $order->getShippingAddress()->getVatId();
        }

        return [
            'company' => $order->getShippingAddress()->getCompany(),
            'vat' => $vatId,
            'attention' => '',
            'address' => $address1,
            'address2' => $address2,
            'city' => $order->getShippingAddress()->getCity(),
            'country' => $order->getShippingAddress()->getCountryId(),
            'email' => $order->getShippingAddress()->getEmail(),
            'phone' => $order->getShippingAddress()->getTelephone(),
            'first_name' => $order->getShippingAddress()->getFirstname(),
            'last_name' => $order->getShippingAddress()->getLastname(),
            'postal_code' => $order->getShippingAddress()->getPostcode(),
            'state_or_province' => $order->getShippingAddress()->getRegion(),
        ];
    }

    /**
     * Get order lines data from order
     *
     * @param Order $order
     * @return array $orderLines
     */
    public function getOrderLines($order)
    {
        $orderTotalDue = $order->getTotalDue() * 100;
        $orderTotalDue = $this->toInt($orderTotalDue);
        $total = 0;
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Item[] $orderitems */
        $orderitems = $order->getAllVisibleItems();

        /** @var \Magento\Sales\Model\Order\Item $orderitem */
        foreach ($orderitems as $orderitem) {
            try {
                $product = $this->productRepository->get($orderitem->getSku());
                $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                if ($subEnabled && !empty($subPlan)) {
                    $line = [
                        'ordertext' => $orderitem->getProduct()->getName(),
                        'amount' => 0,
                        'quantity' => $this->toInt($orderitem->getQtyOrdered()),
                        'vat' => 0,
                        'amount_incl_vat' => "true",
                    ];
                    $orderLines[] = $line;
                    continue;
                }
            } catch (NoSuchEntityException $exception) {
                continue;
            }

            $amount = $orderitem->getPriceInclTax() * 100;
            $amount = round($amount);

            $qty = $orderitem->getQtyOrdered();

            $line = [
                'ordertext' => $orderitem->getProduct()->getName(),
                'amount' => $this->toInt($amount),
                'quantity' => $this->toInt($qty),
                'vat' => $orderitem->getTaxPercent() / 100,
                'amount_incl_vat' => "true",
            ];
            $orderLines[] = $line;

            $total = $total + $this->toInt($amount) * $this->toInt($qty);
        }

        // shipping
        $shippingAmount = ($order->getShippingInclTax() * 100);
        if ($shippingAmount != 0) {
            $line = [
                'ordertext' => !empty($order->getShippingDescription()) ? $order->getShippingDescription() : __('Shipping')->render(),
                'amount' => $this->toInt($shippingAmount),
                'quantity' => 1,
            ];
            if ($order->getShippingTaxAmount() > 0) {
                $line['vat'] = $order->getShippingTaxAmount() / $order->getShippingAmount();
                $line['amount_incl_vat'] = "true";
            } else {
                $line['vat'] = 0;
                $line['amount_incl_vat'] = "true";
            }
            $orderLines[] = $line;
            $total = $total + $this->toInt($shippingAmount);
        }

        // discount
        $discountAmount = ($order->getDiscountAmount() * 100);
        if ($discountAmount != 0) {
            $line = [
                'ordertext' => !empty($order->getDiscountDescription()) ?
                    __('Discount: %1', $order->getDiscountDescription())->render() : __('Discount')->render(),
                'amount' => $this->toInt($discountAmount),
                'quantity' => 1,
                'vat' => 0,
                'amount_incl_vat' => "true",
            ];
            $orderLines[] = $line;
            $total = $total + $this->toInt($discountAmount);
        }

        // other
        if ($total != $orderTotalDue) {
            $amount = $orderTotalDue - $total;
            $line = [
                'ordertext' => __('Other')->render(),
                'amount' => $this->toInt($amount),
                'quantity' => 1,
                'vat' => 0,
                'amount_incl_vat' => "true",
            ];
            //            $orderLines[] = $line;
        }

        return $orderLines;
    }

    /**
     * Get allowwed payment from backend configuration
     *
     * @param Order $order
     * @return array $paymentMethods
     */
    public function getPaymentMethods(Order $order)
    {
        $allowedPaymentConfig = $this->getConfig('allowwed_payment', $order->getStoreId());
        return explode(',', $allowedPaymentConfig);
    }

    /**
     * Prepare payment data
     *
     * @param array $paymentData
     * @return array $paymentData
     */
    public function preparePaymentData(array $paymentData)
    {

        if (array_key_exists('order_lines', $paymentData)) {
            unset($paymentData['order_lines']);
        }
        if (array_key_exists('billing_address', $paymentData)) {
            unset($paymentData['billing_address']);
        }
        if (array_key_exists('shipping_address', $paymentData)) {
            unset($paymentData['shipping_address']);
        }
        if (array_key_exists('amount', $paymentData) && $paymentData['amount'] > 0) {
            $paymentData['amount'] = $paymentData['amount'] / 100;
        }
        if (array_key_exists('refunded_amount', $paymentData) && $paymentData['refunded_amount'] > 0) {
            $paymentData['refunded_amount'] = $paymentData['refunded_amount'] / 100;
        }
        if (array_key_exists('authorized_amount', $paymentData) && $paymentData['authorized_amount'] > 0) {
            $paymentData['authorized_amount'] = $paymentData['authorized_amount'] / 100;
        }
        if (array_key_exists('source', $paymentData)) {
            if (!empty($paymentData['source'])) {
                foreach ($paymentData['source'] as $key => $value) {
                    $paymentData['source_' . $key] = $value;
                }
            }
            unset($paymentData['source']);
        }
        return $paymentData;
    }

    /**
     * Add transaction to order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $paymentData
     * @return string TransactionId
     * @throws PaymentException
     */
    public function addTransactionToOrder($order, $paymentData = [])
    {
        try {
            $paymentData = $this->preparePaymentData($paymentData);

            $state = '';
            $isClosed = 0;
            if ($paymentData['state'] == 'authorized') {
                $state = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                $isClosed = 0;
            } elseif ($paymentData['state'] == 'settled') {
                $state = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $isClosed = 1;
            }

            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['transaction']);
            $payment->setTransactionId($paymentData['transaction']);
            $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData]);

            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['transaction'])
                ->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData])
                ->setFailSafe(true)
                ->build($state)
                ->setIsClosed($isClosed);

            // Add transaction to payment
            $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
            $payment->setParentTransactionId(null);

            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();

            $orderStatusAfterPayment = $this->getConfig('order_status_after_payment', $order->getStoreId());
            if (!empty($orderStatusAfterPayment)) {
                $totalDue = $this->priceHelper->currency($order->getTotalDue(), true, false);

                $order->setState($orderStatusAfterPayment, true);
                $order->setStatus($orderStatusAfterPayment);
                $order->addStatusToHistory($order->getStatus(), 'Billwerk+ : The authorized amount is ' . $totalDue);
                $order->save();
            }

            return $transaction->getTransactionId();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\PaymentException(__('addTransactionToOrder() Exception : ' . $e->getMessage()));
        }
    }

    /**
     * Convert integer amount to 2 decimal places
     *
     * @param int $amount
     * @return string
     */
    public function convertAmount(int $amount)
    {
        return number_format((float)($amount / 100), 2, '.', '');
    }

    /**
     * Prepare capture transaction data
     *
     * @param array $transactionData
     * @return array $transactionData
     */
    public function prepareCaptureTransactionData(array $transactionData)
    {
        if (array_key_exists('amount', $transactionData)) {
            $transactionData['amount'] = $this->convertAmount((int)$transactionData['amount']);
        }

        if (array_key_exists('card_transaction', $transactionData)) {
            $cardTransaction = $transactionData['card_transaction'];
            $transactionData['card_transaction_ref_transaction'] = array_key_exists('ref_transaction', $cardTransaction) ? $cardTransaction['ref_transaction'] : '';
            $transactionData['card_transaction_fingerprint'] = array_key_exists('fingerprint', $cardTransaction) ? $cardTransaction['fingerprint'] : '';
            $transactionData['card_transaction_card_type'] = array_key_exists('card_type', $cardTransaction) ? $cardTransaction['card_type'] : '';
            $transactionData['card_transaction_exp_date'] = array_key_exists('exp_date', $cardTransaction) ? $cardTransaction['exp_date'] : '';
            $transactionData['card_transaction_masked_card'] = array_key_exists('masked_card', $cardTransaction) ? $cardTransaction['masked_card'] : '';
            unset($transactionData['card_transaction']);
        }

        return $transactionData;
    }

    /**
     * Create capture transaction
     *
     * @param Order $order
     * @param array $transactionData
     * @param array $chargeRes
     * @param null|string $authorizationTxnId
     * @return null|int (Magento Transaction ID)
     * @throws PaymentException
     */
    public function addCaptureTransactionToOrder(Order $order, array $transactionData = [], array $chargeRes = [], $authorizationTxnId = null)
    {
        try {
            // prepare transaction data
            $transactionData = $this->prepareCaptureTransactionData($transactionData);

            // prepare payment data from Charge
            $paymentData = $this->preparePaymentData($chargeRes);

            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($transactionData['id']);
            $payment->setTransactionId($transactionData['id']);
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $paymentData]);
            $payment->setParentTransactionId($authorizationTxnId);

            $formatedPrice = $order->getBaseCurrency()->formatTxt($transactionData['amount']);
            $message = __('Billwerk+ : Captured amount of %1 by the webhook.', $formatedPrice);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionData['id'])
                ->setAdditionalInformation([Transaction::RAW_DETAILS => $transactionData])
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->save();
            $order->save();

            $transactionId = $transaction->save()->getTransactionId();

            if ($order->getBillwerkOrderType() === 'Mixed') {
                $orderStatusAfterPayment = $this->getConfig('order_status_after_payment', $order->getStoreId());
                $autoCapture = $this->getConfig('auto_capture', $order->getStoreId());
            } else {
                $orderStatusAfterPayment = 'complete';
                $autoCapture = true;
            }

            if (!empty($orderStatusAfterPayment) && $autoCapture) {
                $totalDue = $this->priceHelper->currency($order->getTotalDue(), true, false);
                $order->setState($orderStatusAfterPayment, true);
                $order->setStatus($orderStatusAfterPayment);
                $order->save();
            }
            return $transactionId;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\PaymentException(__('addCaptureTransactionToOrder() Exception : ' . $e->getMessage()));
        }
    }

    /**
     * Prepare refund transaction data
     *
     * @param array $transactionData
     * @return array $transactionData
     */
    public function prepareRefundTransactionData(array $transactionData)
    {
        if (isset($transactionData['amount'])) {
            $transactionData['amount'] = $this->convertAmount($transactionData['amount']);
        }

        if (isset($transactionData['card_transaction'])) {
            $cardTransaction = $transactionData['card_transaction'];
            unset($transactionData['card_transaction']);
            $transactionData['card_transaction_ref_transaction'] = $cardTransaction['ref_transaction'];
        }

        return $transactionData;
    }

    /**
     * Create refund transaction
     *
     * @param Order $order
     * @param array $transactionData
     * @param array $chargeRes
     * @return int (Magento Transaction ID)
     * @throws PaymentException
     */
    public function addRefundTransactionToOrder(Order $order, array $transactionData = [], array $chargeRes = [])
    {
        try {
            // prepare transaction data
            $transactionData = $this->prepareRefundTransactionData($transactionData);

            // prepare payment data from Charge
            $paymentData = $this->preparePaymentData($chargeRes);

            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($transactionData['id']);
            $payment->setTransactionId($transactionData['id']);
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $paymentData]);

            $formatedPrice = $order->getBaseCurrency()->formatTxt($transactionData['amount']);
            $message = __('Billwerk+ : Refunded amount of %1 by the webhook.', $formatedPrice);

            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionData['id'])
                ->setAdditionalInformation([Transaction::RAW_DETAILS => (array)$transactionData])
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_REFUND);

            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\PaymentException(__('addRefundTransactionToOrder() Exception : ' . $e->getMessage()));
        }
    }

    /**
     * Check is billwerk+ payment method
     *
     * @param string $method
     * @return bool
     */
    public function isOurPaymentMethod($method = '')
    {
        if (in_array($method, self::PAYMENT_METHODS, true)) {
            return true;
        }
        return false;
    }

    /**
     * Convert variable to integer
     *
     * @param float|string $number
     * @return int
     */
    public function toInt($number)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (gettype($number) == "double") {
            $number = round($number);
        }
        return (int)($number . "");
    }

    /**
     * Set billwerk+ payment state to radarsofthouse_reepay_status
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param string $state
     * @return void
     * @throws LocalizedException
     * @throws \Exception
     */
    public function setReepayPaymentState(Order\Payment $payment, $state)
    {
        $_additionalInfo = $payment->getAdditionalInformation();
        $_additionalInfo['raw_details_info']['state'] = $state;
        $payment->setAdditionalInformation($_additionalInfo);
        $payment->save();

        $orderId = $payment->getOrder()->getIncrementId();
        $orderStatus = $this->orderStatusFactory->create()->load($orderId, 'order_increment_id');
        if ($orderStatus->getStatusId()) {
            $orderStatus->setStatus($state);
            $orderStatus->save();
        }
    }

    /**
     * Check order is mixed order.
     *
     * @param Order $order
     * @return bool
     */
    public function isMixedOrder($order)
    {
        $currentOrderType = $order->getBillwerkOrderType();
        if (!empty($currentOrderType)) {
            return $currentOrderType === 'Mixed';
        }
        $isNormal = false;
        $isSubscription = false;
        foreach ($order->getItems() as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                if (!$subEnabled || empty($subPlan)) {
                    $isSubscription = true;
                } else {
                    $isNormal = true;
                }
            } catch (NoSuchEntityException $noSuchEntityException) {
                continue;
            }
        }
        return ($isNormal && $isSubscription);
    }

    /**
     * Return true if the product is Billwerk+ subscrition prodict
     *
     * @param Product $product
     * @return bool
     */
    public function isBillwerkSubscriptionProduct(Product $product)
    {
        return $product->getBillwerkSubEnabled() && $product->getBillwerkSubPlan() && !empty($product->getBillwerkSubPlan());
    }
}
