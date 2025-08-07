<?php
namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Magento\InventorySourceDeductionApi\Model;

use Exception;
use Magento\Framework\Registry;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class SourceDeductionService
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var SourceDeductionRequestInterfaceFactory
     */
    protected $sourceDeductionRequestFactory;
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var Logger
     */
    protected $logger;


    /**
     * SourceDeductionService constructor.
     *
     * @param Registry $registry
     * @param SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory
     * @param Data $helperData
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory,
        Data $helperData,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->sourceDeductionRequestFactory = $sourceDeductionRequestFactory;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Prevent MSI source deduction
     *
     * @param \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $subject
     * @param callable $proceed
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @return null|\Magento\InventorySourceDeductionApi\Api\Data\SourceDeduction
     * @throws Exception
     */
    public function aroundExecute(
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $subject,
        callable $proceed,
        SourceDeductionRequestInterface $sourceDeductionRequest
    ) {
        if ($this->shouldSkipStockReduction()) {
            $this->logger->addInfo('MSI Source deduction prevented for source: ' . $sourceDeductionRequest->getSourceCode());
            $items = $sourceDeductionRequest->getItems();
            $this->logger->addInfo('Items count: ' . count($items));
            foreach ($items as $key => $item) {
                if ($this->helperData->isBillwerkSubscriptionProductBySku($item->getSku())) {
                    $this->logger->addInfo('Skipping stock deduction for subscription product SKU: ' . $item->getSku());
                    unset($items[$key]); // Remove subscription products from deduction
                }
            }
            if (empty($items)) {
                $this->logger->addInfo('No items left for source deduction after filtering subscription products.');
                return null; // No items to process
            }
            // Rebuild the source deduction request without subscription products
            $rebuildSourceDeductionRequest = $this->sourceDeductionRequestFactory->create([
                'sourceCode' => $sourceDeductionRequest->getSourceCode(),
                'items' => $items,
                'salesChannel' => $sourceDeductionRequest->getSalesChannel(),
                'salesEvent' => $sourceDeductionRequest->getSalesEvent(),
            ]);
            return $proceed($rebuildSourceDeductionRequest);
        }
        return $proceed($sourceDeductionRequest);
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
