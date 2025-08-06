<?php
namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Magento\CatalogInventory\Model;

use Exception;
use Magento\Framework\Registry;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class StockManagement
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
     * StockManagement constructor.
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
     * Prevent stock registration for products
     *
     * @param \Magento\CatalogInventory\Model\StockManagement $subject
     * @param callable $proceed
     * @param array $items
     * @param int|null $websiteId
     * @return \Magento\CatalogInventory\Model\StockManagement
     * @throws Exception
     */
    public function aroundRegisterProductsSale(\Magento\CatalogInventory\Model\StockManagement $subject, callable $proceed, $items, $websiteId = null)
    {
        if ($this->shouldSkipStockReduction()) {
            $this->logger->addInfo('Stock registration prevented for ' . count($items) . ' items');
            foreach ($items as $productId => $item) {
                if ($this->helperData->isBillwerkSubscriptionProductById($productId)) {
                    $this->logger->addInfo('Skipping stock registration for product ID: ' . $productId);
                    unset($items[$productId]); // Remove subscription products from sale registration
                }
            }
        }
        return $proceed($items, $websiteId);
    }

    /**
     * Prevent stock revert
     *
     * @param \Magento\CatalogInventory\Model\StockManagement $subject
     * @param callable $proceed
     * @param array $items
     * @param int|null $websiteId
     * @return \Magento\CatalogInventory\Model\StockManagement
     * @throws Exception
     */
    public function aroundRevertProductsSale(\Magento\CatalogInventory\Model\StockManagement $subject, callable $proceed, $items, $websiteId = null)
    {
        if ($this->shouldSkipStockReduction()) {
            $this->logger->addInfo('Stock revert prevented for ' . count($items) . ' items');
            foreach ($items as $productId => $item) {
                if ($this->helperData->isBillwerkSubscriptionProductById($productId)) {
                    $this->logger->addInfo('Skipping stock revert for product ID: ' . $productId);
                    unset($items[$productId]); // Remove subscription products from stock revert
                }
            }
        }

        return $proceed($items, $websiteId);
    }

    /**
     * Check if stock reduction should be skipped
     *
     * @return bool
     */
    protected function shouldSkipStockReduction()
    {
        return $this->registry->registry('skip_stock_reduction') === true;
    }
}
