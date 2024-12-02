<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Checkout;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Email;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class SubmitAllAfter implements ObserverInterface
{

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ToOrderItem
     */
    protected $toOrderItem;

    /**
     * @var Email
     */
    protected $emailHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ToOrderItem $toOrderItem
     * @param Email $emailHelper
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ToOrderItem $toOrderItem,
        Email $emailHelper,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->toOrderItem = $toOrderItem;
        $this->emailHelper = $emailHelper;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(
        Observer $observer
    ) {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /** @var OrderInterface $order */
        /** @var OrderInterface $originalOrder */
        $order = $originalOrder = $observer->getEvent()->getOrder();

        if ($quote->isMultipleShippingAddresses()
            || !$originalOrder->getPayment()
            || $originalOrder->getPayment()->getMethod() !== 'billwerkplus_subscription') {
            return;
        }

        $this->logger->addInfo(__METHOD__ . ' Begin modify price of subscription item to zero.');
        $this->logger->addInfo(__METHOD__, ['quoteId' => $quote->getId(), 'orderId' => $order->getId()]);

        $reduceBaseTotal = 0;
        $reduceTotal = 0;
        $reduceBaseTax = 0;
        $reduceTax = 0;
        $discount = 0;
        $discountBase = 0;
        $discountTaxBase = 0;
        $discountTax = 0;

        $parentIds = [];
        $items = $quote->getAllItems();
        /** @var Item $quoteItem */
        foreach ($items as $quoteItem) {
            $origOrderItem = $order->getItemByQuoteItemId($quoteItem->getId());
            $productId = $origOrderItem->getProductId();
            if ($productId) {
                $product = $this->productRepository->getById($productId);
                if ($product->getBillwerkSubEnabled() && $product->getBillwerkSubPlan()
                    && !empty($product->getBillwerkSubPlan())) {
                    $this->logger->addInfo(
                        __METHOD__,
                        ['productId' => $product->getId(), 'quoteItemId' => $quoteItem->getId()]
                    );
                    $this->setZero(
                        $quoteItem,
                        $reduceBaseTotal,
                        $reduceTotal,
                        $reduceBaseTax,
                        $reduceTax,
                        $discountTaxBase,
                        $discountTax,
                        $discount,
                        $discountBase
                    );
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
                    $this->logger->addInfo(__METHOD__, ['quoteItemId' => $quoteItem->getId()]);
                    $this->setZero(
                        $quoteItem,
                        $reduceBaseTotal,
                        $reduceTotal,
                        $reduceBaseTax,
                        $reduceTax,
                        $discountTaxBase,
                        $discountTax,
                        $discount,
                        $discountBase
                    );
                }
            }
        }

        $quote->collectTotals();
        $quote->save();

        foreach ($items as $quoteItem) {
            $orderItem = $this->toOrderItem->convert($quoteItem);
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

        $order->setSubtotal(($order->getSubtotal() - $reduceTotal));
        $order->setBaseSubtotal(($order->getBaseSubtotal() - $reduceBaseTotal));
        $order->setSubtotalInclTax(($order->getSubtotalInclTax() - $reduceBaseTotal - $reduceBaseTax - $discountTax));
        $order->setBaseSubtotalInclTax(
            ($order->getBaseSubtotalInclTax() - $reduceBaseTotal - $reduceBaseTax - $discountTaxBase)
        );
        $order->setDiscountTaxCompensationAmount(0);
        $order->setDiscountAmount(0);
        $order->setBaseDiscountTaxCompensationAmount(0);
        $order->setBaseDiscountAmount(0);
        $order->setTaxAmount(($order->getTaxAmount() - $reduceTax));
        $order->setBaseTaxAmount(($order->getBaseTaxAmount() - $reduceBaseTax));
        $order->setGrandTotal(($order->getGrandTotal() - $reduceTotal - $reduceTax + ($discount - $discountTax)));
        $order->setBaseGrandTotal(
            ($order->getBaseGrandTotal() - $reduceBaseTotal - $reduceBaseTax + ($discountBase - $discountTaxBase))
        );
        $quote->save();
        $order->save();

        $this->logger->addInfo(__METHOD__ . 'Finish modify price of subscription item to zero.');

        $this->emailHelper->sendOrderConfirmationEmail($order->getIncrementId());
    }

    /**
     *  Set subscription item to zero.
     *
     * @param Item $quoteItem
     * @param int $reduceBaseTotal
     * @param int $reduceTotal
     * @param int $reduceBaseTax
     * @param int $reduceTax
     * @param int $discountTaxBase
     * @param int $discountTax
     * @param int $discount
     * @param int $discountBase
     * @return void
     */
    public function setZero(
        Item &$quoteItem,
        &$reduceBaseTotal,
        &$reduceTotal,
        &$reduceBaseTax,
        &$reduceTax,
        &$discountTaxBase,
        &$discountTax,
        &$discount,
        &$discountBase
    ) {
        $reduceBaseTotal += $quoteItem->getBasePrice();
        $reduceTotal += $quoteItem->getPrice();
        $reduceBaseTax += $quoteItem->getBaseTaxAmount();
        $reduceTax += $quoteItem->getTaxAmount();
        $discountTaxBase += $quoteItem->getBaseDiscountTaxCompensationAmount();
        $discountTax += $quoteItem->getDiscountTaxCompensationAmount();
        $discount += $quoteItem->getDiscountAmount();
        $discountBase += $quoteItem->getBaseDiscountAmount();
        $quoteItem->setBasePrice(0);
        $quoteItem->setPrice(0);
        $quoteItem->setCustomPrice(0);
        $quoteItem->setOriginalCustomPrice(0);
        $quoteItem->setDiscountTaxCompensationAmount(0);
        $quoteItem->setBaseDiscountTaxCompensationAmount(0);
        $quoteItem->setBaseDiscountAmount(0);
        $quoteItem->setDiscountAmount(0);
        $quoteItem->setDiscountPercent(0);
        $quoteItem->setBaseTaxAmount(0);
        $quoteItem->setTaxAmount(0);
        $quoteItem->setBaseRowTotal(0);
        $quoteItem->setRowTotal(0);
        $quoteItem->setBasePriceInclTax(0);
        $quoteItem->setPriceInclTax(0);
        $quoteItem->setBaseRowTotalInclTax(0);
        $quoteItem->setRowTotalInclTax(0);
        $quoteItem->setConvertedPrice(0);
    }
}
