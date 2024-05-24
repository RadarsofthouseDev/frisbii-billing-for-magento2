<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Sales;

use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use StripeIntegration\Payments\Exception\LocalizedException;

class OrderPaymentPlaceStart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Observe sales_order_payment_place_start
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getPayment();
        $paymentMethod = $payment->getMethod();
        if ($this->helper->isOurPaymentMethod($paymentMethod)) {
            $storeId = $payment->getOrder()->getStoreId();
            try {
                if ($this->helper->getConfig('send_order_email_when_success', $storeId)) {
                    $observer->getPayment()->getOrder()
                        ->setCanSendNewEmailFlag(false)
                        ->setIsCustomerNotified(false)
                        ->save();
                }
            } catch (LocalizedException $exception) {
                return;
            }
        }
    }
}
