<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Charge;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Refund;

class PaymentMethod extends Adapter
{
    /** @var string  */
    protected $_code = 'billwerkplus_subscription';
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Charge
     */
    protected $helperCharge;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Refund
     */
    protected $helperRefund;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param Data $helper
     * @param Charge $helperCharge
     * @param Refund $helperRefund
     * @param Registry $registry
     * @param Logger $helperLogger
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerInterface          $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory  $paymentDataObjectFactory,
        string                    $code,
        string                    $formBlockType,
        string                    $infoBlockType,
        Data                      $helper,
        Charge                    $helperCharge,
        Refund                    $helperRefund,
        Registry                  $registry,
        Logger                    $helperLogger,
        MessageManagerInterface   $messageManager,
        CommandPoolInterface      $commandPool = null,
        ValidatorPoolInterface    $validatorPool = null,
        CommandManagerInterface   $commandExecutor = null,
        LoggerInterface           $logger = null
    ) {
        $this->helper = $helper;
        $this->helperCharge = $helperCharge;
        $this->helperRefund = $helperRefund;
        $this->registry = $registry;
        $this->logger = $helperLogger;
        $this->messageManager = $messageManager;
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
    }

    /**
     * Is offline
     *
     * @return false
     */
    public function isOffline()
    {
        return false;
    }

    /**
     * Can Capture
     *
     * @return bool
     */
    public function canCapture()
    {
        return true;
    }

    /**
     * Can Capture Partial
     *
     * @return bool
     */
    public function canCapturePartial()
    {
        return true;
    }

    /**
     * Can Refund
     *
     * @return bool
     * @throws LocalizedException
     */
    public function canRefund()
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        return $order->getBillwerkOrderType() === 'Mixed' || $this->registry->registry('billwerk_subscription_webhook');
    }

    /**
     * Can Refund Partial per invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        return true;
    }

    /**
     * Can Void
     *
     * @return bool
     */
    public function canVoid()
    {
        return true;
    }

    /**
     * Check Quote is available
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote) {
            if ($quote->getItems()) {
                foreach ($quote->getItems() as $item) {
                    try {
                        if (in_array($item->getProductType(), ['simple', 'virtual'])) {
                            if ($this->helper->isBillwerkSubscriptionProductById($item->getProductId())) {
                                return true;
                            }
                        } elseif ($item->getProductType() === 'configurable') {
                            if ($item->getHasChildren()) {
                                foreach ($item->getChildren() as $child) {
                                    if ($this->helper->isBillwerkSubscriptionProductById($child->getProductId())) {
                                        return true;
                                    }
                                }
                            }
                        }
                    } catch (NoSuchEntityException $e) {
                        return false;
                    }
                }
            }
        }
        if ($this->registry->registry('billwerk_subscription_webhook')) {
            return true;
        }
        return false;
    }

    /**
     * Capture Order
     *
     * @param InfoInterface $payment
     * @param float|string $amount
     * @return $this|Adapter|PaymentMethod
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        if ($order->getBillwerkOrderType() !== 'Mixed') {
            return $this;
        }

        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $chargeRes = $this->helperCharge->get(
            $apiKey,
            $order->getIncrementId()
        );

        if ($this->helper->getConfig('auto_capture', $order->getStoreId())) {
            if (!empty($chargeRes)) {
                if ($chargeRes['state'] == 'settled') {
                    $this->logger->addDebug("auto capture is enabled : skip to settle again");
                    return $this;
                }
            }
        }

        if (array_key_exists('authorized_amount', $chargeRes) && $chargeRes['authorized_amount'] > 0) {
            $tmp_amount = $amount;
            $authorized_amount = $chargeRes['authorized_amount'];

            if ($this->helper->toInt($amount * 100) > $authorized_amount) {
                $amount = $authorized_amount / 100;
            }
            if ($amount != $tmp_amount) {
                $this->logger->addDebug(
                    "Change capture amount from {$tmp_amount} to {$amount} for order" . $order->getIncrementId()
                );
            }
        }
        $this->logger->addDebug(
            __METHOD__,
            ['capture : ' . $order->getIncrementId() . ', amount : ' . $amount]
        );

        $options = [];

        if (!$this->helper->getConfig('send_order_line', $order->getStoreId())) {
            $_amount = $amount * 100;
            $options['amount'] = $this->helper->toInt($_amount);
        }

        $_amount = $amount * 100;
        $options['amount'] = $this->helper->toInt($_amount);

        $charge = null;
        if ($this->registry->registry('is_settled_webhook') == 1) {
            // When invoice created from the settled webhook then don't do the settle request to Reepay
            $this->logger->addDebug(
                "Skip settle request to Frisbii when invoice is created from Frisbii settled webhook"
            );
            $charge = $chargeRes;
        } else {
            $charge = $this->helperCharge->settle(
                $apiKey,
                $order->getIncrementId(),
                $options
            );
        }

        if (!empty($charge)) {
            if (isset($charge["error"])) {
                $this->logger->addDebug("settle error : ", $charge);
                $error_message = $charge["error"];
                if (isset($charge["message"])) {
                    $error_message = $charge["error"] . " : " . $charge["message"];
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($error_message));
            }

            if ($charge['state'] === 'settled') {
                $_payment = $order->getPayment();
                $this->helper->setReepayPaymentState($_payment, 'settled');
                $order->save();

                $this->logger->addDebug('settled : ' . $order->getIncrementId());

                // separate transactions for partial capture
                $payment->setIsTransactionClosed(false);
                $payment->setTransactionId($charge['transaction']);
                $transactionData = [
                    'handle' => $charge['handle'],
                    'transaction' => $charge['transaction'],
                    'state' => $charge['state'],
                    'amount' => $amount,
                    'customer' => $charge['customer'],
                    'currency' => $charge['currency'],
                    'created' => $charge['created'],
                    'authorized' => $charge['authorized'],
                    'settled' => $charge['settled']
                ];
                $payment->setTransactionAdditionalInfo(
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    $transactionData
                );

                $this->logger->addDebug('set capture transaction data');
            }
        } else {
            $this->logger->addDebug("Empty settle response from Frisbii");
            $this->messageManager->addError("Empty settle response from Frisbii");
            throw new \Magento\Framework\Exception\LocalizedException(__("Empty settle response from Frisbii"));
        }
        return $this;
    }

    /**
     * Refund order
     *
     * @param InfoInterface $payment
     * @param float|string $amount
     * @return $this|Adapter|PaymentMethod
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if ($this->registry->registry('billwerk_subscription_webhook')) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $isOnline = $creditmemo->getDoTransaction();
        $order = $payment->getOrder();

        if ($order->getBillwerkOrderType() !== 'Mixed') {
            $this->logger->addDebug(
                __METHOD__,
                ['offline_refund : ' . $order->getIncrementId() . ', amount : ' . $amount]
            );
            return $this;
        }

        if (!$isOnline) {
            $this->logger->addDebug(
                __METHOD__,
                ['offline_refund : ' . $order->getIncrementId() . ', amount : ' . $amount]
            );
            return $this;
        }

        $this->logger->addDebug(
            __METHOD__,
            ['online_refund : ' . $order->getIncrementId() . ', amount : ' . $amount]
        );

        $options = [];
        $options['invoice'] = $order->getIncrementId();
        $_amount = $amount * 100;
        $options['amount'] = $this->helper->toInt($_amount);
        $options['ordertext'] = "refund";

        $apiKey = $this->helper->getApiKey($order->getStoreId());

        $refund = $this->helperRefund->create(
            $apiKey,
            $options
        );
        if (!empty($refund)) {
            if (isset($refund["error"])) {
                $this->logger->addDebug("refund error : ", $refund);
                $error_message = $refund["error"];
                if (isset($refund["message"])) {
                    $error_message = $refund["error"] . " : " . $refund["message"];
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($error_message));
            }

            if ($refund['state'] == 'refunded') {
                $_payment = $order->getPayment();
                $this->helper->setReepayPaymentState($_payment, 'refunded');
                $order->save();

                // separate transactions for partial refund
                $payment->setIsTransactionClosed(false);
                $payment->setTransactionId($refund['transaction']);
                $transactionData = [
                    'invoice' => $refund['invoice'],
                    'transaction' => $refund['transaction'],
                    'state' => $refund['state'],
                    'amount' => $this->helper->convertAmount($refund['amount']),
                    'type' => $refund['type'],
                    'currency' => $refund['currency'],
                    'created' => $refund['created']
                ];

                $payment->setTransactionAdditionalInfo(
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    $transactionData
                );

                $this->logger->addDebug("set refund transaction data");
            }
        } else {
            $this->logger->addDebug("Empty refund response from Frisbii");
            $this->messageManager->addErrorMessage("Empty refund response from Frisbii");
            throw new \Magento\Framework\Exception\LocalizedException(__('Empty refund response from Frisbii'));
        }
        return $this;
    }
}
