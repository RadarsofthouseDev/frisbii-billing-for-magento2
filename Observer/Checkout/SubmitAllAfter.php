<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Checkout;

use Exception;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;

class SubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /** @var OrderInterface $order */
        /** @var OrderInterface $originalOrder */
        $order = $originalOrder = $observer->getEvent()->getOrder();

        if ($originalOrder->getPayment()->getMethod() !== 'billwerkplus_subscription') {
            return;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Quote\Model\Quote\Item\ToOrderItem $quoteToOrder */
        $quoteToOrder = $objectManager->create(\Magento\Quote\Model\Quote\Item\ToOrderItem::class);

        $reduceBaseTotal = 0;
        $reduceTotal = 0;
        $reduceBaseTax = 0;
        $reduceTax = 0;

        $parentIds = [];
        $items =  $quote->getAllItems();
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($items as $quoteItem) {
            $origOrderItem = $order->getItemByQuoteItemId($quoteItem->getId());
            $orderItemId = $origOrderItem->getItemId();
            $productId = $origOrderItem->getProductId();

            if ($productId) {
                $product = $objectManager->create(\Magento\Catalog\Model\Product::class)->load($productId);
                if ($product->getBillwerkSubEnabled() && $product->getBillwerkSubPlan()
                    && !empty($product->getBillwerkSubPlan())) {
                    $reduceBaseTotal += $quoteItem->getBasePrice();
                    $reduceTotal += $quoteItem->getPrice();
                    $reduceBaseTax += $quoteItem->getBaseTaxAmount();
                    $reduceTax += $quoteItem->getTaxAmount();
                    $quoteItem->setBasePrice(0);
                    $quoteItem->setPrice(0);
                    $quoteItem->setBaseTaxAmount(0);
                    $quoteItem->setTaxAmount(0);
                    $quoteItem->setBaseRowTotal(0);
                    $quoteItem->setRowTotal(0);
                    $quoteItem->setBasePriceInclTax(0);
                    $quoteItem->setPriceInclTax(0);
                    $quoteItem->setBaseRowTotalInclTax(0);
                    $quoteItem->setRowTotalInclTax(0);
                    $parentId = $quoteItem->getParentItemId();
                    if ($parentId) {
                        $parentIds[] = $parentId;
                    }

                }
            }
        }

        if (!empty($parentIds)) {
            foreach ($items as $quoteItem) {
                if (in_array($quoteItem->getId(), $parentIds)) {
                    $reduceBaseTotal += $quoteItem->getBasePrice();
                    $reduceTotal += $quoteItem->getPrice();
                    $reduceBaseTax += $quoteItem->getBaseTaxAmount();
                    $reduceTax += $quoteItem->getTaxAmount();
                    $quoteItem->setBasePrice(0);
                    $quoteItem->setPrice(0);
                    $quoteItem->setBaseTaxAmount(0);
                    $quoteItem->setTaxAmount(0);
                    $quoteItem->setBaseRowTotal(0);
                    $quoteItem->setRowTotal(0);
                    $quoteItem->setBasePriceInclTax(0);
                    $quoteItem->setPriceInclTax(0);
                    $quoteItem->setBaseRowTotalInclTax(0);
                    $quoteItem->setRowTotalInclTax(0);
                }
            }
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote->collectTotals();
        $quote->save();

        foreach ($items as $quoteItem) {
            $orderItem = $quoteToOrder->convert($quoteItem);
            $origOrderItemNew = $order->getItemByQuoteItemId($quoteItem->getId());
            if ($origOrderItemNew) {
                $origOrderItemNew->addData($orderItem->getData());
            } else {
                if ($quoteItem->getParentItem()) {
                    $orderItem->setParentItem(
                        $order->getItemByQuoteItemId($orderItem->getParentItem()->getId())
                    );
                }
                $order->addItem($orderItem);
            }
        }

        $order->setSubtotal(($order->getSubtotal() - $reduceTotal))
            ->setBaseSubtotal(($order->getBaseSubtotal() - $reduceBaseTotal))
            ->setSubtotalInclTax(($order->getSubtotalInclTax() - $reduceBaseTotal - $reduceBaseTax))
            ->setBaseSubtotalInclTax(($order->getBaseSubtotalInclTax() - $reduceBaseTotal - $reduceBaseTax))
            ->setTaxAmount(($order->getTaxAmount() - $reduceTax))
            ->setBaseTaxAmount(($order->getBaseTaxAmount() - $reduceBaseTax))
            ->setGrandTotal(($order->getGrandTotal() -  $reduceTotal - $reduceTax))
            ->setBaseGrandTotal(($order->getBaseGrandTotal() -  $reduceBaseTotal - $reduceBaseTax));
        $quote->save();
        $order->save();

        /** @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Email $helperEmail */
        $helperEmail = $objectManager->create(\Radarsofthouse\BillwerkPlusSubscription\Helper\Email ::class);
        $helperEmail->sendOrderConfirmationEmail($order->getIncrementId());
    }
}
