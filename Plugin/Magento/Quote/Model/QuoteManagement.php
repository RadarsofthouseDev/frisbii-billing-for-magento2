<?php
namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Magento\Quote\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class QuoteManagement
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
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * QuoteManagement constructor.
     *
     * @param Registry $registry
     * @param OrderRepositoryInterface $orderRepository
     * @param SerializerInterface $serializer
     * @param ResourceConnection $resourceConnection
     * @param Data $helperData
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        OrderRepositoryInterface $orderRepository,
        SerializerInterface $serializer,
        ResourceConnection $resourceConnection,
        Data $helperData,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->resourceConnection = $resourceConnection;
        $this->helper = $helperData;
        $this->logger = $logger;
    }

    /**
     * Set flag before quote submission if stock reduction should be skipped
     *
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param Quote $quote
     * @param array $orderData
     * @return array
     */
    public function beforeSubmit(\Magento\Quote\Model\QuoteManagement $subject, $quote, $orderData = [])
    {
        // If Payment method are not Frisbii
        if ($quote->isMultipleShippingAddresses() || !$quote->getPayment() || !$this->helper->isOurPaymentMethod($quote->getPayment()->getMethod())) {
            return [$quote, $orderData];
        }

        if ($this->shouldSkipStockReductionForQuote($quote)) {
            $this->registry->unregister('skip_stock_reduction');
            $this->registry->register('skip_stock_reduction', true);

            // Set inventory processed to prevent automatic stock reduction
            $quote->setInventoryProcessed(true);

            $this->logger->addInfo('Stock reduction disabled for quote ID: ' . $quote->getId());
        }

        return [$quote, $orderData];
    }

    /**
     * Clean up registry after quote submission
     *
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param mixed $result
     * @param Quote $quote
     * @param array $orderData
     * @return mixed
     */
    public function afterSubmit(\Magento\Quote\Model\QuoteManagement $subject, $result, $quote, $orderData = [])
    {
        if ($quote->isMultipleShippingAddresses() || !$quote->getPayment() || !$this->helper->isOurPaymentMethod($quote->getPayment()->getMethod())) {
            return $result;
        }

        if ($this->registry->registry('skip_stock_reduction')) {
            //$this->updateReservation($quote);
            $this->registry->unregister('skip_stock_reduction');
        }

        return $result;
    }

    /**
     * Update reservation for the quote items
     *
     * @param Quote $quote
     */
    protected function updateReservation($quote)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');
        $orderIncrementId = $quote->getReservedOrderId();
        if (!$orderIncrementId) {
            $this->logger->addError('Order ID is not set for quote ID: ' . $quote->getId());
            return;
        }

        // Update reservations for all items in the quote
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($this->helper->isBillwerkSubscriptionProductById($item->getProduct()->getId())) {
                $sku = $item->getSku();
                $query = $connection->select()
                    ->where('sku = ?', $sku)
                    ->where('quantity <> ?', 0.000)
                    ->where('metadata LIKE ?', '%"object_increment_id":"' . $orderIncrementId . '"%')
                    ->order(['reservation_id DESC'])
                    ->from($tableName);
                $queryItems = $connection->fetchAll($query);
                if (empty($queryItems)) {
                    $this->logger->addInfo('No reservations found for SKU: ' . $sku . ' in quote ID: ' . $quote->getId());
                    continue;
                }
                foreach ($queryItems as $queryItem) {
                    $metadata = $this->serializer->unserialize($queryItem['metadata']);
                    if (isset($metadata['event_type'], $metadata['object_increment_id']) && $metadata['event_type'] === 'order_placed' && $metadata['object_increment_id'] === $orderIncrementId) {
                        $reservationId = $queryItem['reservation_id'];
                        $affectedRows =$connection->update(
                            $tableName,
                            ['quantity' => 0.000], // Set quantity to negative to prevent stock reduction
                            ['reservation_id = ?' => $reservationId]
                        );
                        if ($affectedRows === 0) {
                            $this->logger->addError('Failed to update reservation for SKU: ' . $sku . ' with ID: ' . $reservationId);
                            continue;
                        }
                        $this->logger->addInfo('Updated reservation for SKU: ' . $sku . ' with ID: ' . $reservationId . ' to prevent stock reduction.');
                    }
                }
            } else {
                $this->logger->addInfo('Skipping non-billwerk subscription product SKU: ' . $item->getSku() . ' in quote ID: ' . $quote->getId());
            }
        }
    }

    /**
     * Check if stock reduction should be skipped for the quote
     *
     * @param Quote $quote
     * @return bool
     */
    protected function shouldSkipStockReductionForQuote($quote)
    {
        if ($this->registry->registry('billwerk_subscription_webhook')) {
            return false;
        }

        // Check if any item has the skip flag
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($this->helper->isBillwerkSubscriptionProductById($item->getProduct()->getId())) {
                return true;
            }
        }

        return false;
    }
}
