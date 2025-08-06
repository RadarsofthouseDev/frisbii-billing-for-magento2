<?php
namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Magento\CatalogInventory\Model\Stock;

use Exception;
use Magento\Framework\Registry;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class Item
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Item constructor.
     *
     * @param Registry $registry
     * @param Data $helperData
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        Data $helperData,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Prevent stock reduction when saving stock item
     *
     * @param \Magento\CatalogInventory\Model\Stock\Item $subject
     * @param callable $proceed
     * @return \Magento\CatalogInventory\Model\Stock\Item
     * @throws Exception
     */
    public function aroundSave(\Magento\CatalogInventory\Model\Stock\Item $subject, callable $proceed)
    {
        if ($this->shouldSkipStockReduction($subject)) {
            // Store original values
            $originalQty = $subject->getOrigData('qty');
            $originalIsInStock = $subject->getOrigData('is_in_stock');

            // Get current values before potential modification
            $currentQty = $subject->getQty();

            // If this is a stock reduction operation, restore original values
            if ($originalQty !== null && $currentQty < $originalQty) {
                $subject->setQty($originalQty);
                $subject->setIsInStock($originalIsInStock);

                $this->logger->addInfo('Stock reduction prevented for product ID: ' . $subject->getProductId());
            }
        }

        return $proceed();
    }

    /**
     * Prevent quantity subtraction
     *
     * @param \Magento\CatalogInventory\Model\Stock\Item $subject
     * @param callable $proceed
     * @param float $qty
     * @return \Magento\CatalogInventory\Model\Stock\Item
     * @throws Exception
     */
    public function aroundSubtractQty(\Magento\CatalogInventory\Model\Stock\Item $subject, callable $proceed, $qty)
    {
        if ($this->shouldSkipStockReduction($subject)) {
            $this->logger->addInfo('Stock subtraction prevented for product ID: ' . $subject->getProductId() . ', qty: ' . $qty);
            return $subject; // Return without subtracting
        }

        return $proceed($qty);
    }

    /**
     * Check if stock reduction should be skipped
     *
     * @param \Magento\CatalogInventory\Model\Stock\Item $item
     * @return bool
     */
    protected function shouldSkipStockReduction($item)
    {
        return $this->registry->registry('skip_stock_reduction') === true &&
               $this->helperData->isBillwerkSubscriptionProductById($item->getProductId());
    }
}
