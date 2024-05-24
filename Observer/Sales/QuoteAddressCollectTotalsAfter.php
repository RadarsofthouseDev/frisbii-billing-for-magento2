<?php
/**
 * Copyright © BillwerkPlusSubscription All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Sales;

use Magento\Framework\Registry;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class QuoteAddressCollectTotalsAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Construct
     *
     * @param Registry $registry
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        $total = $observer->getTotal();

        $this->logger->addInfo(__METHOD__);

        if ($this->registry->registry('billwerk_subscription_webhook_renewal_order')) {

            $shipping = $total->getData('shipping_incl_tax');
            $baseShipping = $total->getData('base_shipping_incl_tax');

            if ($shipping <= 0) {
                return;
            }

            $grandTotal = $total->getData('grand_total');
            $baseGrandTotal = $total->getData('base_grand_total');

            $observer->getTotal()->setTotalAmount('shipping', 0);
            $observer->getTotal()->setTotalAmount('shipping_tax_calculation', 0);
            $observer->getTotal()->setTotalAmount('shipping_discount_tax_compensation', 0);
            $observer->getTotal()->setTotalAmount('shipping_tax', 0);
            $observer->getTotal()->setTotalAmount('shipping_discount', 0);

            $observer->getTotal()->setBaseTotalAmount('shipping', 0);
            $observer->getTotal()->setBaseTotalAmount('shipping_tax_calculation', 0);
            $observer->getTotal()->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
            $observer->getTotal()->setBaseTotalAmount('shipping_tax', 0);
            $observer->getTotal()->setBaseTotalAmount('shipping_discount', 0);

            $observer->getTotal()->setData('shipping_incl_tax', 0);
            $observer->getTotal()->setData('base_shipping_incl_tax', 0);
            $observer->getTotal()->setData('shipping_amount_for_discount', 0);
            $observer->getTotal()->setData('base_shipping_amount_for_discount', 0);
            $observer->getTotal()->setData('grand_total', round($grandTotal - $shipping, 2));
            $observer->getTotal()->setData('base_grand_total', round($baseGrandTotal - $baseShipping, 2));
        }
    }
}
