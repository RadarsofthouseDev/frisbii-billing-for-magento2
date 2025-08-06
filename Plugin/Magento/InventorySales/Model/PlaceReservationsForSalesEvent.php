<?php
/**
 * Copyright © Radarsofthouse All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Magento\InventorySales\Model;

use Magento\Framework\Registry;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class PlaceReservationsForSalesEvent
{

    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * PlaceReservationsForSalesEvent constructor.
     *
     * @param Registry $registry
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helperData
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        OrderRepositoryInterface $orderRepository,
        Data $helperData,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->orderRepository = $orderRepository;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    public function beforeExecute(
        \Magento\InventorySales\Model\PlaceReservationsForSalesEvent $subject,
        array $items,
        SalesChannelInterface $salesChannel,
        SalesEventInterface $salesEvent
    ): array {
        if ($this->shouldSkipStockReduction($salesEvent)) {
            $this->logger->addInfo('Stock reduction prevented for sales event: ' . $salesEvent->getType() . ' with object ID: ' . $salesEvent->getObjectId());
            foreach ($items as $key => $item) {
                if ($item instanceof \Magento\InventorySalesApi\Api\Data\ItemToSellInterface) {
                    if ($this->helperData->isBillwerkSubscriptionProductBySku($item->getSku())) {
                        $this->logger->addInfo('Skipping stock reduction for subscription product SKU: ' . $item->getSku());
                        unset($items[$key]);
                    }
                }
            }
        }
        return [$items, $salesChannel, $salesEvent];
    }

    /**
     * Check if stock reduction should be skipped
     *
     * @param SalesEventInterface $salesEvent
     * @return bool
     */
    protected function shouldSkipStockReduction($salesEvent)
    {
        if ($this->registry->registry('skip_stock_reduction')) {
            return true;
        }

        if (in_array($salesEvent->getType(), [SalesEventInterface::EVENT_ORDER_CANCELED, SalesEventInterface::EVENT_INVOICE_CREATED, SalesEventInterface::EVENT_CREDITMEMO_CREATED, SalesEventInterface::EVENT_SHIPMENT_CREATED])) {
            $orderId = $salesEvent->getObjectId();
            if ($orderId) {
                try {
                    $order = $this->orderRepository->get($orderId);
                    if (in_array($order->getBillwerkOrderType(), ['Subscription', 'Mixed'])) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Log exception or handle it as needed
                }
            }

        }
        return false;
    }
}
