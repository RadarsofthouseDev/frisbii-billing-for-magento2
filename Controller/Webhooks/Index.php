<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Webhooks;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriptionRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Charge;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Email;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Invoice as InvoiceHelper;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;
use Radarsofthouse\BillwerkPlusSubscription\Model\StatusFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var OrderInterface
     */
    protected $orderInterface;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var TransactionSearchResultInterfaceFactory
     */
    protected $transactionSearchResultInterfaceFactory;

    /**
     * @var StatusFactory
     */
    protected $orderStatus;

    /**
     * @var Charge
     */
    protected $chargeHelper;

    /**
     * @var Email
     */
    protected $helperEmail;

    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Subscription
     */
    protected $subscriptionHelper;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var ConvertOrder
     */
    protected $convertOrder;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var CustomerSubscriptionInterfaceFactory
     */
    protected $customerSubscriptionFactory;
    /**
     * @var CustomerSubscriberInterfaceFactory
     */
    protected $customerSubscriberFactory;
    /**
     * @var CustomerSubscriptionRepositoryInterface
     */
    protected $customerSubscriptionRepository;
    /**
     * @var CustomerSubscriberRepositoryInterface
     */
    protected $customerSubscriberRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param TransactionFactory $transactionFactory
     * @param Registry $registry
     * @param Order $order
     * @param ConvertOrder $convertOrder
     * @param OrderService $orderService
     * @param OrderInterface $orderInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param Invoice $invoice
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param CreditmemoService $creditmemoService
     * @param InvoiceService $invoiceService
     * @param StatusFactory $orderStatus
     * @param Charge $chargeHelper
     * @param InvoiceHelper $invoiceHelper
     * @param Subscription $subscriptionHelper
     * @param Logger $logger
     * @param Data $helper
     * @param Email $helperEmail
     * @param CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory
     * @param CustomerSubscriberInterfaceFactory $customerSubscriberFactory
     * @param CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param CartManagementInterface $cartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteFactory $quoteFactory
     * @param StoreManagerInterface $storeManager
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        TransactionFactory $transactionFactory,
        Registry $registry,
        Order $order,
        ConvertOrder $convertOrder,
        OrderService $orderService,
        OrderInterface $orderInterface,
        OrderRepositoryInterface $orderRepository,
        TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory,
        CreditmemoFactory $creditmemoFactory,
        Invoice $invoice,
        OrderCollectionFactory $orderCollectionFactory,
        CreditmemoService $creditmemoService,
        InvoiceService $invoiceService,
        StatusFactory $orderStatus,
        Charge $chargeHelper,
        InvoiceHelper $invoiceHelper,
        Subscription $subscriptionHelper,
        Logger $logger,
        Data $helper,
        Email $helperEmail,
        CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory,
        CustomerSubscriberInterfaceFactory $customerSubscriberFactory,
        CustomerSubscriptionRepositoryInterface $customerSubscriptionRepository,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->invoiceHelper = $invoiceHelper;
        $this->order = $order;
        $this->convertOrder = $convertOrder;
        $this->orderService = $orderService;
        $this->orderInterface = $orderInterface;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceService = $invoiceService;
        $this->helper = $helper;
        $this->helperEmail = $helperEmail;
        $this->invoice = $invoice;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transactionSearchResultInterfaceFactory = $transactionSearchResultInterfaceFactory;
        $this->orderStatus = $orderStatus;
        $this->chargeHelper = $chargeHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->customerSubscriptionFactory = $customerSubscriptionFactory;
        $this->customerSubscriberFactory = $customerSubscriberFactory;
        $this->customerSubscriptionRepository = $customerSubscriptionRepository;
        $this->customerSubscriberRepository = $customerSubscriberRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->cartManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->context = $context;
        parent::__construct($context);

        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists(\Magento\Framework\App\CsrfAwareActionInterface::class)) {
            $request = $this->getRequest();

            if ($request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    /**
     * Execute
     *
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $request = $this->getRequest()->getContent();
        $receiveData = json_decode($request, true);

        $this->logger->addDebug(__METHOD__, $receiveData);

        try {
            if (!array_key_exists('event_type', $receiveData)) {
                throw new Exception('This request event_type not found.', 404);
            }
            switch ($receiveData['event_type']) {
                case 'invoice_refund':
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    sleep(5);
                    $response = $this->refund($receiveData);
                    $log['response'] = $response;
                    $this->logger->addDebug('Refund response', $log);
                    break;

                case 'invoice_settled':
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    sleep(5);
                    if (array_key_exists('subscription', $receiveData)) {
                        $response = $this->subscriptionInvoiceSettled($receiveData);
                    } else {
                        $response = $this->settled($receiveData);
                    }
                    $log['response'] = $response;
                    $this->logger->addDebug('Settled response', $log);
                    break;

                case 'invoice_cancelled':
                    if (array_key_exists('subscription', $receiveData)) {
                        $response['status'] = 200;
                        $response['message'] = 'This request is not charge invoice.';
                    } else {
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        sleep(5);
                        $response = $this->cancel($receiveData);
                    }
                    $log['response'] = $response;
                    $this->logger->addDebug('Cancel response', $log);
                    break;

                case 'invoice_authorized':
                    if (array_key_exists('subscription', $receiveData)) {
                        $response['status'] = 200;
                        $response['message'] = 'This request is not charge invoice.';
                    } else {
                        $response = $this->authorize($receiveData);
                    }
                    $log['response'] = $response;
                    $this->logger->addDebug('Authorized response', $log);
                    break;

                case 'subscription_renewal':
                    $log['response'] = $response = $this->subscriptionRenewal($receiveData);
                    $this->logger->addDebug('Subscription Renewal response', $log);
                    break;

                case 'subscription_cancelled':
                    $log['response'] = $response = $this->subscriptionInActive($receiveData);
                    $this->logger->addDebug('Subscription Cancelled response', $log);
                    break;

                case 'subscription_uncancelled':
                    $log['response'] = $response = $this->subscriptionActive($receiveData);
                    $this->logger->addDebug('Subscription Uncancelled response', $log);
                    break;

                case 'subscription_expired':
                    $log['response'] = $response = $this->subscriptionInActive($receiveData);
                    $this->logger->addDebug('Subscription Expired response', $log);
                    break;

                case 'subscription_on_hold':
                    $log['response'] = $response = $this->subscriptionInActive($receiveData);
                    $this->logger->addDebug('Subscription On hold response', $log);
                    break;

                case 'subscription_reactivated':
                    $log['response'] = $response = $this->subscriptionActive($receiveData);
                    $this->logger->addDebug('Subscription Reactivated response', $log);
                    break;

                default:
                    $response['status'] = 200;
                    $response['message'] = 'The ' . $receiveData['event_type'] . ' event has been ignored by Magento.';
                    $log['response'] = $response;
                    $this->logger->addDebug('default', $log);
                    break;
            }
            $response['message'] = 'Magento : ' . $response['message'];
            $result = $this->resultJsonFactory->create();
            $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
            $result->setHttpResponseCode($response['status']);
            $result->setData($response);

            return $result;
        } catch (LocalizedException | Exception $e) {
            $log['response_error'] = [
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ];
            $this->logger->addError(__METHOD__, $log);
            throw new Exception($e->getMessage(), (!empty($e->getCode()) ? (int)$e->getCode() : 500));
        }
    }

    /**
     * Capture invoice from Frisbii
     *
     * @param array $data
     * @return array
     * @throws PaymentException
     */
    protected function settled(array $data)
    {
        $order_id = $data['invoice'];
        $this->logger->addDebug(__METHOD__, [$order_id]);
        $order = $this->orderInterface->loadByIncrementId($order_id);
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $metadata = $this->invoiceHelper->getMetadata($apiKey, $data['invoice']);
        if (!isset($metadata['magento']['module']) || $metadata['magento']['module'] !== 'subscription') {
            return [
                'status' => 200,
                'message' => "This order not created from subscription module.",
            ];
        }

        try {
            if (!$order->getId()) {
                $this->logger->addError('The order #' . $order_id . ' no longer exists.');
                return [
                    'status' => 500,
                    'message' => 'The order #' . $order_id . ' no longer exists.'
                ];
            }

            if ($order->getBillwerkOrderType() !== 'Mixed') {
                return [
                    'status' => 200,
                    'message' => "This order not support settled function.",
                ];
            }

            $apiKey = $this->helper->getApiKey($order->getStoreId());
            $metaData = $this->invoiceHelper->getMetadata($apiKey, $order_id);

            if (!isset($metaData['magento']['module']) || $metaData['magento']['module'] !== 'subscription') {
                return [
                    'status' => 200,
                    'message' => 'The order #' . $order_id . ' not from Subscription module.'
                ];
            }

            $transactionData = $this->invoiceHelper->getTransaction($apiKey, $order_id, $data['transaction']);

            if (!empty($transactionData['id']) && $transactionData['type'] == "settle") {
                // check the transaction has been created
                $transactions = $this->transactionSearchResultInterfaceFactory->create()
                    ->addOrderIdFilter($order->getId());
                $hasTxn = false;
                $authorizationTxnId = null;
                foreach ($transactions->getItems() as $transaction) {
                    if ($transaction->getTxnId() == $transactionData['id']) {
                        $hasTxn = true;
                    }
                    if ($transaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
                        $authorizationTxnId = $transaction->getTxnId();
                    }
                }

                $chargeRes = $this->chargeHelper->get($apiKey, $order_id);

                $_invoiceType = "";
                $_createInvoice = false;

                if ($this->helper->getConfig('auto_capture', $order->getStoreId())) {
                    $_invoiceType = 'auto_capture';
                    $_createInvoice = true;
                }

                if (
                    !$_createInvoice && $this->helper->getConfig('auto_create_invoice', $order->getStoreId())
                ) {
                    if (
                        isset($chargeRes['state']) && $chargeRes['state'] == "settled"
                        && $chargeRes['amount'] == ($order->getGrandTotal() * 100)
                    ) {
                        $_invoiceType = 'settled_in_frisbii';
                        $_createInvoice = true;
                    }
                }

                $this->registry->register('is_reepay_settled_webhook', 1);

                if ($hasTxn) {
                    if ($_createInvoice && $order->canInvoice()) {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                        $invoice->register();
                        $invoice->getOrder()->setCustomerNoteNotify(false);
                        $invoice->getOrder()->setIsInProcess(true);
                        $invoice->setState(Invoice::STATE_PAID);
                        $transactionSave = $this->transactionFactory->create()->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transactionSave->save();

                        $this->logger->addDebug("#1 : Automatic create invoice for the order #" . $order_id .
                            " : Invoice type => " . $_invoiceType);
                    }

                    $this->logger->addDebug("Magento have created the transaction '" . $transactionData['id'] .
                        "' already.");

                    return [
                        'status' => 200,
                        'message' => "Magento have created the transaction '" . $transactionData['id'] . "' already.",
                    ];
                }

                $transactionID = $this->helper
                    ->addCaptureTransactionToOrder($order, $transactionData, $chargeRes, $authorizationTxnId);
                if ($transactionID) {
                    $this->helper->setReepayPaymentState($order->getPayment(), 'settled');
                    $order->save();

                    if ($_createInvoice && $order->canInvoice()) {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                        $invoice->register();
                        $invoice->getOrder()->setCustomerNoteNotify(false);
                        $invoice->getOrder()->setIsInProcess(true);
                        $invoice->setState(Invoice::STATE_PAID);
                        $transactionSave = $this->transactionFactory->create()->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transactionSave->save();

                        $this->logger->addDebug("#2 : Automatic create invoice for the order #" . $order_id .
                            " : Invoice type => " . $_invoiceType);
                    }

                    $this->logger->addDebug('Settled order #' . $order_id . " , transaction ID : " . $transactionID);

                    return [
                        'status' => 200,
                        'message' => 'Settled order #' . $order_id . " , transaction ID : " . $transactionID,
                    ];
                } else {
                    $this->logger->addError('Cannot create capture transaction for order #' . $order_id .
                        " , transaction : " . $transactionData['id']);

                    return [
                        'status' => 500,
                        'message' => 'Cannot create capture transaction for order #' . $order_id .
                            " , transaction : " . $transactionData['id'],
                    ];
                }
            } else {
                $this->logger->addError('Cannot get transaction data from Frisbii : transaction ID = ' .
                    $data['transaction']);

                return [
                    'status' => 500,
                    'message' => 'Cannot get transaction data from Frisbii : transaction ID = ' . $data['transaction']
                ];
            }
        } catch (Exception $e) {
            $this->logger->addError('settled webhook exception : ' . $e->getMessage());

            return [
                'status' => 500,
                'message' => 'settled webhook exception : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Refund from Frisbii
     *
     * @param array $data
     * @return array error message
     */
    protected function refund(array $data)
    {
        $orderId = $data['invoice'];
        $invoiceId = $data['invoice'];
        if (array_key_exists('subscription', $data)) {
            $collectionRenewal = $this->orderCollectionFactory->create();
            $collectionRenewal->addAttributeToFilter('billwerk_order_type', 'Renewal')
                ->addAttributeToFilter('billwerk_sub_handle', $data['subscription'])
                ->addAttributeToFilter('billwerk_sub_inv_handle', $data['invoice'])->load();

            $orderId = $collectionRenewal->getFirstItem()->getData('increment_id');
            if (!$collectionRenewal->getTotalCount()) {
                return [
                    'status' => 500,
                    'message' => 'not found order.',
                ];
            }
        }
        $this->logger->addDebug(__METHOD__, [$orderId]);
        $order = $this->orderInterface->loadByIncrementId($orderId);
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        if (!array_key_exists('subscription', $data)) {
            $metadata = $this->invoiceHelper->getMetadata($apiKey, $data['invoice']);
            if (!isset($metadata['magento']['module']) || $metadata['magento']['module'] !== 'subscription') {
                return [
                    'status' => 200,
                    'message' => "This order not created from subscription module.",
                ];
            }
        }
        try {
            if (!$order->getId()) {
                $this->logger->addError('The order #' . $orderId . ' no longer exists.');

                return [
                    'status' => 500,
                    'message' => 'The order #' . $orderId . ' no longer exists.'
                ];
            }

            if (!in_array($order->getBillwerkOrderType(), ['Mixed', 'Renewal'])) {
                return [
                    'status' => 200,
                    'message' => "This order not support refund function.",
                ];
            }

            $this->registry->register('billwerk_subscription_webhook', true);

            $refundData = $this->invoiceHelper->getTransaction($apiKey, $invoiceId, $data['transaction']);

            if (!empty($refundData['id']) && $refundData['state'] == "refunded") {

                // check the transaction has been created
                $transactions = $this->transactionSearchResultInterfaceFactory->create()
                    ->addOrderIdFilter($order->getId());
                $hasTxn = false;
                foreach ($transactions->getItems() as $transaction) {
                    if ($transaction->getTxnId() == $refundData['id']) {
                        $hasTxn = true;
                    }
                }

                if ($hasTxn) {
                    $this->logger->addDebug("Magento have created the transaction '" .
                        $refundData['id'] . "' already.");
                    return [
                        'status' => 200,
                        'message' => "Magento have created the transaction '" . $refundData['id'] . "' already.",
                    ];
                }

                $creditMemoIncrementId = null;
                if (
                    $order->canCreditmemo() && $order->getBillwerkOrderType() === 'Renewal'
                    && $order->getInvoiceCollection()->getTotalCount()
                ) {
                    $refundAmount = (float)$this->helper->convertAmount($refundData['amount']);
                    $availableAmount = round($order->getTotalInvoiced() - $order->getTotalRefunded(), 2);
                    $isPartial = true;
                    if ($availableAmount == $refundAmount) {
                        $isPartial = false;
                    }
                    $invoice = $order->getInvoiceCollection()->getFirstItem();
                    $creditMemoData = $invoice->getData();
                    $qtys = [];
                    if ($isPartial) {
                        foreach ($order->getItems() as $item) {
                            $qtys[$item->getItemId()] = 0;
                        }
                        $creditMemoData['qtys'] = $qtys;
                    }
                    $creditMemo = $this->creditmemoFactory->createByInvoice($invoice, $creditMemoData);
                    if ($isPartial) {
                        if ($creditMemo->getGrandTotal() < $refundAmount) {
                            $creditMemoData['adjustment_positive'] =
                                round($refundAmount - $creditMemo->getGrandTotal(), 2);
                        } elseif ($creditMemo->getGrandTotal() > $refundAmount) {
                            $creditMemoData['adjustment_negative'] =
                                round($creditMemo->getGrandTotal() - $refundAmount, 2);
                        }
                    } else {
                        if ($creditMemo->getGrandTotal() > $availableAmount) {
                            $creditMemoData['adjustment_negative'] =
                                round($creditMemo->getGrandTotal() - $availableAmount, 2);
                        }
                    }
                    $creditMemo = $this->creditmemoFactory->createByInvoice($invoice, $creditMemoData);
                    $creditMemo = $this->creditmemoService->refund($creditMemo, false);
                    $creditMemoIncrementId = $creditMemo->getIncrementId();
                }

                // create refund transaction
                $chargeRes = $this->chargeHelper->get($apiKey, $invoiceId);

                $transactionID = $this->helper->addRefundTransactionToOrder($order, $refundData, $chargeRes);

                if ($transactionID && $creditMemoIncrementId) {
                    $this->helper->setReepayPaymentState($order->getPayment(), 'refunded');
                    $order->save();

                    $this->logger->addDebug('Refunded order #' . $orderId . " ,CreditMemo #'.
                    $creditMemoIncrementId . ', transaction ID : " . $transactionID);

                    return [
                        'status' => 200,
                        'message' => 'Refunded order #' . $orderId . " ,CreditMemo #'. $creditMemoIncrementId .
                        ', transaction ID : " . $transactionID,
                    ];
                } elseif ($transactionID) {
                    $this->helper->setReepayPaymentState($order->getPayment(), 'refunded');
                    $order->save();
                    $this->logger->addDebug('Refunded order #' . $orderId . " , transaction ID : " .
                        $transactionID);
                    return [
                        'status' => 200,
                        'message' => 'Refunded order #' . $orderId . " , transaction ID : " . $transactionID,
                    ];
                } else {
                    $this->logger->addError('Cannot create refund transaction for order #' . $orderId .
                        " , transaction : " . $refundData['id']);
                    return [
                        'status' => 500,
                        'message' => 'Cannot create refund transaction for order #' . $orderId . " , transaction : " .
                            $refundData['id'],
                    ];
                }
            } else {
                $this->logger->addError('Cannot get refund transaction data from Frisbii : transaction ID = '
                    . $data['transaction']);
                return [
                    'status' => 500,
                    'message' => 'Cannot get refund transaction data from Frisbii : transaction ID = ' .
                        $data['transaction'],
                ];
            }
        } catch (Exception $e) {
            $this->logger->addError('refund webhook exception : ' . $e->getMessage());

            return [
                'status' => 500,
                'message' => 'refund webhook exception : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel from Frisbii
     *
     * @param array $data
     * @return array
     */
    protected function cancel(array $data)
    {
        $order_id = $data['invoice'];
        $this->logger->addDebug(__METHOD__, [$order_id]);
        $order = $this->orderInterface->loadByIncrementId($order_id);
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $metadata = $this->invoiceHelper->getMetadata($apiKey, $data['invoice']);
        if (!isset($metadata['magento']['module']) || $metadata['magento']['module'] !== 'subscription') {
            return [
                'status' => 200,
                'message' => "This order not created from subscription module.",
            ];
        }
        try {
            if (!$order->getId()) {
                $this->logger->addError('The order #' . $order_id . ' no longer exists.');

                return [
                    'status' => 500,
                    'message' => 'The order #' . $order_id . ' no longer exists.'
                ];
            }

            if ($order->getBillwerkOrderType() !== 'Mixed') {
                return [
                    'status' => 200,
                    'message' => "This order not support cancel function.",
                ];
            }

            if (!$order->canCancel()) {
                $this->logger->addError('Cannot cancel this order');

                if ($order->getState() == Order::STATE_CANCELED) {
                    return [
                        'status' => 200,
                        'message' => 'The order was cancelled in Magento'
                    ];
                }

                return [
                    'status' => 500,
                    'message' => 'Cannot cancel this order'
                ];
            }

            $order->cancel();
            $order->addStatusHistoryComment('Frisbii : order have been cancelled by the webhook');
            $order->save();

            $_payment = $order->getPayment();
            $this->helper->setReepayPaymentState($_payment, 'cancelled');
            $order->save();

            $this->logger->addDebug('cancelled order #' . $order_id);

            return [
                'status' => 200,
                'message' => 'cancelled order #' . $order_id
            ];
        } catch (Exception $e) {
            $this->logger->addError('cancel webhook exception : ' . $e->getMessage());

            return [
                'status' => 500,
                'message' => 'cancel webhook exception : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create authorize transaction if you have no the transaction
     *
     * @param array $data
     * @return array
     * @throws PaymentException
     */
    protected function authorize(array $data)
    {
        $order_id = $data['invoice'];
        $this->logger->addDebug(__METHOD__, [$order_id]);
        $order = $this->orderInterface->loadByIncrementId($order_id);
        $apiKey = $this->helper->getApiKey($order->getStoreId());
        $metadata = $this->invoiceHelper->getMetadata($apiKey, $data['invoice']);
        if (!isset($metadata['magento']['module']) || $metadata['magento']['module'] !== 'subscription') {
            return [
                'status' => 200,
                'message' => "This order not created from subscription module.",
            ];
        }
        try {
            // check if has reepay status row for the order, That means the order has been authorized
            $orderStatus = $this->orderStatus->create()->load($order_id, 'order_increment_id');
            if ($orderStatus->getStatusId() && $orderStatus->getStatus() === 'authorized') {
                $this->logger->addDebug('order #' . $order_id . ' has been authorized already');

                return [
                    'status' => 200,
                    'message' => 'order #' . $order_id . ' has been authorized already',
                ];
            }

            $apiKey = $this->helper->getApiKey($order->getStoreId());
            $chargeRes = $this->chargeHelper->get(
                $apiKey,
                $order_id
            );

            // add Reepay payment data
            $data = [
                'order_id' => $order->getId(),
                'order_increment_id' => $order_id,
                'order_type' => $order->getBillwerkOrderType(),
                'email' => $order->getCustomerEmail(),
                'masked_card_number' => $chargeRes['source']['masked_card'] ?? '',
                'fingerprint' => $chargeRes['source']['fingerprint'] ?? '',
                'card_type' => $chargeRes['source']['card_type'] ?? '',
                'status' => $chargeRes['state'],
            ];

            $newOrderStatus = $this->orderStatus->create();
            $newOrderStatus->setData($data);
            $newOrderStatus->save();
            $this->logger->addDebug('save reepay status');

            $this->helper->addTransactionToOrder($order, $chargeRes);
            $this->logger->addDebug('order #' . $order_id . ' has been authorized by the webhook');

            return [
                'status' => 200,
                'message' => 'order #' . $order_id . ' has been authorized by the webhook',
            ];
        } catch (Exception $e) {
            $this->logger->addError('webhook authorize exception : ' . $e->getMessage());

            return [
                'status' => 500,
                'message' => 'webhook authorize error : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get base order
     *
     * @param string $subscriptionHandle
     * @return array|mixed|null
     */
    protected function getBaseOrderId($subscriptionHandle)
    {
        $collectionOrder = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('billwerk_order_type', ['in' => ['Mixed', 'Subscription']])
            ->addAttributeToFilter('billwerk_sub_handle', $subscriptionHandle)->load();

        if ($collectionOrder->getTotalCount()) {
            return $collectionOrder->getFirstItem()->getData('entity_id');
        }
        return null;
    }

    /**
     * Create Renewal order
     *
     * @param array $data
     * @return array
     */
    protected function subscriptionRenewal($data)
    {
        try {
            $this->registry->register('billwerk_subscription_webhook', true);
            $this->registry->register('billwerk_subscription_webhook_renewal_order', true);

            $collectionRenewal = $this->orderCollectionFactory->create()
                ->addAttributeToFilter('billwerk_order_type', 'Renewal')
                ->addAttributeToFilter('billwerk_sub_handle', $data['subscription'])
                ->addAttributeToFilter('billwerk_sub_inv_handle', $data['invoice'])->load();

            if ($collectionRenewal->getTotalCount()) {
                return [
                    'status' => 200,
                    'message' => 'order already exist.',
                ];
            }

            $baseOrderId = $this->getBaseOrderId($data['subscription']);

            if (!$baseOrderId) {
                return [
                    'status' => 500,
                    'message' => 'not found base order.',
                ];
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quoteRepository = $objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);

            $baseOrder = $this->orderRepository->get($baseOrderId);
            $baseQuote = $quoteRepository->get($baseOrder->getQuoteId());

            $apiKey = $this->helper->getApiKey($baseOrder->getStoreId());
            $invoice = $this->invoiceHelper->get($apiKey, $data['invoice']);

            if ($invoice['amount'] == 0) {
                return [
                    'status' => 200,
                    'message' => 'skip create order with zero amount.',
                ];
            }

            $productIds = [];
            $productOptions = [];

            foreach ($baseOrder->getItems() as $item) {
                $productIds[$item->getProductId()] = $item->getQtyOrdered();
                $productOptions[$item->getProductId()] = null;
                $options = $item->getProductOptions();
                if (
                    isset($options['info_buyRequest']['options']) &&
                    is_array($options['info_buyRequest']['options'])
                ) {
                    $productOptions[$item->getProductId()] = $options['info_buyRequest']['options'];
                }
            }

            $store = $this->storeManager->getStore($baseOrder->getStoreId());
            $this->storeManager->setCurrentStore($store);
            $quote = $this->quoteFactory->create();
            $quote->setStore($store);

            $this->logger->addDebug(__METHOD__, [$baseOrder->getCustomerId(), $baseOrder->getEntityId()]);

            $customer = $this->customerRepository->getById($baseOrder->getCustomerId());
            $quote->setCurrency();
            $quote->assignCustomer($customer);
            $quote->setSendConfirmation(0);
            $quote->save();

            foreach ($productIds as $id => $qty) {
                $product = $this->productRepository->getById($id);
                if (
                    $product->getBillwerkSubEnabled() && $product->getBillwerkSubPlan()
                    && !empty($product->getBillwerkSubPlan())
                ) {
                    $options = $productOptions[$id];
                    if ($options) {
                        $quote->addProduct($product, new \Magento\Framework\DataObject([
                            'qty' => (int)$qty,
                            'options' => $options
                        ]));
                    } else {
                        $quote->addProduct($product, (int)$qty);
                    }
                }
            }
            $quote->save();

            $customPrice = $invoice['amount'] / 100;
            foreach ($productIds as $id => $qty) {
                $product = $this->productRepository->getById($id);
                if (
                    $product->getBillwerkSubEnabled() && $product->getBillwerkSubPlan()
                    && !empty($product->getBillwerkSubPlan())
                ) {
                    $productItem = $quote->getItemByProduct($product);
                    $productItem->setCustomPrice($customPrice);
                    $productItem->setOriginalCustomPrice($customPrice);
                    $productItem->getProduct()->setIsSuperMode(true);
                    $productItem->save();
                }
            }

            $quote->save();
            $this->logger->addDebug(__METHOD__, [$baseQuote->getId()]);
            $this->logger->addDebug(__METHOD__, [$baseQuote->getShippingAddress()->getId()]);
            $this->logger->addDebug(__METHOD__, [$baseQuote->getShippingAddress()->getCountryId()]);
            $this->logger->addDebug(__METHOD__, [$baseQuote->getBillingAddress()->getCountryId()]);

            $quote->setBillingAddress($baseQuote->getBillingAddress());
            $quote->setShippingAddress($baseQuote->getShippingAddress());

            $this->logger->addDebug('Shipping:' . $baseQuote->getShippingAddress()->getShippingMethod());
            $this->logger->addDebug(__METHOD__, [$quote->getShippingAddress()->getCountryId()]);
            $this->logger->addDebug(__METHOD__, [$quote->getBillingAddress()->getCountryId()]);
            $quote->getShippingAddress()
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($baseQuote->getShippingAddress()->getShippingMethod());
            $quote->setPaymentMethod('billwerkplus_subscription');
            $quote->setInventoryProcessed(false);
            $quote->save();

            $quote->getPayment()->importData(['method' => 'billwerkplus_subscription']);

            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
            $this->logger->addDebug(__METHOD__ . __LINE__);
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals()
                ->save();
            $this->logger->addDebug(__METHOD__ . __LINE__);
            $service = $this->cartManagement->submit($quote);
            $this->logger->addDebug(__METHOD__ . __LINE__);
            $increment_id = $service->getRealOrderId();
            $orderId = $service->getId();
            $this->logger->addDebug(__METHOD__ . __LINE__);
            $order = $this->orderRepository->get($orderId);
            $order->setBillwerkOrderType('Renewal');
            $order->setBillwerkSubHandle($data['subscription']);
            $order->setBillwerkSubInvHandle($data['invoice']);
            $order->save();
            $this->logger->addDebug(__METHOD__ . __LINE__);
            return [
                'status' => 200,
                'message' => 'created renewal order #' . $increment_id,
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => 'webhook error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Capture the invoice for renewal order from Frisbii
     *
     * @param array $data
     * @param int $try
     * @return array
     * @throws LocalizedException
     */
    protected function subscriptionInvoiceSettled($data, int $try = 0)
    {
        $baseOrderId = $this->getBaseOrderId($data['subscription']);

        if (!$baseOrderId) {
            return [
                'status' => 500,
                'message' => 'not found base order.',
            ];
        }

        $baseOrder = $this->orderRepository->get($baseOrderId);

        $apiKey = $this->helper->getApiKey($baseOrder->getStoreId());
        $invoiceBW = $this->invoiceHelper->get($apiKey, $data['invoice']);

        if ($invoiceBW['amount'] == 0) {
            return [
                'status' => 200,
                'message' => 'skip create invoice with zero amount.',
            ];
        }

        $collectionRenewal = $this->orderCollectionFactory->create();
        $collectionRenewal->addAttributeToFilter('billwerk_order_type', 'Renewal')
            ->addAttributeToFilter('billwerk_sub_handle', $data['subscription'])
            ->addAttributeToFilter('billwerk_sub_inv_handle', $data['invoice'])->load();

        if (!$collectionRenewal->getTotalCount()) {
            if ($try <= 10) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                sleep(1);
                $try++;
                $this->subscriptionInvoiceSettled($data, $try);
            }
            return [
                'status' => 500,
                'message' => 'not found order.',
            ];
        }

        $orderId = $collectionRenewal->getFirstItem()->getData('entity_id');
        $order = $this->orderRepository->get($orderId);
        $storeId = $order->getStoreId();
        $autoInvoice = $this->helper->getConfig('auto_create_invoice_after_renewal', $storeId);
        $autoShipment = $this->helper->getConfig('auto_create_invoice_after_renewal', $storeId);

        if ($order->canInvoice() && $autoInvoice) {
            try {
                $apiKey = $this->helper->getApiKey($order->getStoreId());
                if (array_key_exists('transaction', $data)) {
                    $paymentTransactionData = $this->invoiceHelper
                        ->getTransaction($apiKey, $data['invoice'], $data['transaction']);
                } else {
                    $paymentTransactionData = [];
                }
                $chargeRes = $this->invoiceHelper->get($apiKey, $data['invoice']);
                $this->logger->addDebug("transaction: " . $data['invoice'], $paymentTransactionData);
                $authorizationTxnId = null;
                if (!empty($paymentTransactionData['id']) && $paymentTransactionData['type'] == "settle") {
                    $transactions = $this->transactionSearchResultInterfaceFactory->create()
                        ->addOrderIdFilter($order->getId());
                    $hasTxn = false;
                    foreach ($transactions->getItems() as $transaction) {
                        if ($transaction->getTxnId() == $paymentTransactionData['id']) {
                            $hasTxn = true;
                        }
                        if ($transaction->getTxnType() == TransactionInterface::TYPE_AUTH) {
                            $authorizationTxnId = $transaction->getTxnId();
                        }
                    }
                }
                $this->registry->register('is_settled_webhook', 1);
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->setState(Invoice::STATE_PAID);
                $invoice->save();
                $transactionSave = $this->transactionFactory->create()->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->helper
                    ->addCaptureTransactionToOrder($order, $paymentTransactionData, $chargeRes, $authorizationTxnId);
                $order->setStatus('complete');
                $order->save();
                $this->helperEmail->sendOrderRenewalEmail($order->getIncrementId());
                if (!$order->canShip() || !$autoShipment) {
                    $baseOrderId = $this->getBaseOrderId($data['subscription']);
                    if ($baseOrderId) {
                        $baseOrder = $this->orderRepository->get($baseOrderId);
                        if ($baseOrder->getBillwerkOrderType() === 'Subscription') {
                            $orderStatus = $this->helper
                                ->getConfig('order_status_after_renewal', $baseOrder->getStoreId());
                            $baseOrder->setStatus($orderStatus);
                            $baseOrder->save();
                        }
                    }
                    return [
                        'status' => 200,
                        'message' => 'created invoice #' . $invoice->getId(),
                    ];
                }
            } catch (LocalizedException | Exception $e) {
                return [
                    'status' => 500,
                    'message' => 'webhook error: ' . $e->getMessage(),
                ];
            }
        }

        if ($order->canShip() && $autoShipment) {
            $orderShipment = $this->convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $qty = $orderItem->getQtyToShip();
                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                $orderShipment->addItem($shipmentItem);
            }
            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);
            try {
                $orderShipment->save();
                $orderShipment->getOrder()->save();
                $baseOrderId = $this->getBaseOrderId($data['subscription']);
                if ($baseOrderId) {
                    $baseOrder = $this->orderRepository->get($baseOrderId);
                    if ($baseOrder->getBillwerkOrderType() === 'Subscription') {
                        $orderStatus = $this->helper
                            ->getConfig('order_status_after_renewal', $baseOrder->getStoreId());
                        $baseOrder->setStatus($orderStatus);
                        $baseOrder->save();
                    }
                }
                if (isset($invoice)) {
                    return [
                        'status' => 200,
                        'message' => 'created invoice #' . $invoice->getId() . ' and shipment #' .
                            $orderShipment->getId(),
                    ];
                } else {
                    return [
                        'status' => 200,
                        'message' => 'created shipment #' . $orderShipment->getId(),
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'status' => 500,
                    'message' => 'webhook error: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'status' => 200,
            'message' => 'invoice already exist.',
        ];
    }

    /**
     *  Update Subscription status.
     *
     * @param string $customerHandle
     * @return void
     * @throws LocalizedException
     */
    protected function updateCustomerSubscriber($customerHandle)
    {
        /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber */
        $customerSubscriber = $this->customerSubscriberFactory->create();
        $customerSubscriber->load($customerHandle, 'customer_handle');
        if ($customerSubscriber->getCustomerId()) {
            $this->searchCriteriaBuilder->addFilter('customer_handle', $customerHandle);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            try {
                $customerSubscriptions = $this->customerSubscriptionRepository->getList($searchCriteria);
                if ($customerSubscriptions->getTotalCount()) {
                    foreach ($customerSubscriptions->getItems() as $item) {
                        if ($item->getStatus() === 'active') {
                            $customerSubscriber->setSubscriptionActive(1);
                            $this->customerSubscriberRepository->save($customerSubscriber);
                            return;
                        }
                    }
                }
                $customerSubscriber->setSubscriptionActive(0);
                $this->customerSubscriberRepository->save($customerSubscriber);
                return;
            } catch (LocalizedException $e) {
                throw new LocalizedException(__('Something went wrong.'));
            }
        }
        throw new LocalizedException(__('Not found customer.'));
    }

    /**
     * Update Subscription statusto active.
     *
     * @param array $data
     * @return array
     */
    protected function subscriptionActive($data)
    {
        $subscriptionHandle = $data['subscription'];
        $customerHandle = $data['customer'];
        /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription */
        $customerSubscription = $this->customerSubscriptionFactory->create();
        $customerSubscription->load($subscriptionHandle, 'subscription_handle');
        if ($customerSubscription->getEntityId()) {
            $customerSubscription->setStatus('active');
            try {
                $this->customerSubscriptionRepository->save($customerSubscription);
                $this->updateCustomerSubscriber($customerHandle);
            } catch (LocalizedException $e) {
                return [
                    'status' => 500,
                    'message' => 'settled webhook exception : ' . $e->getMessage(),
                ];
            }
        }
        return [
            'status' => 500,
            'message' => 'Not found subscription'
        ];
    }

    /**
     * Update Subscription status to in_active.
     *
     * @param array $data
     * @return array
     */
    protected function subscriptionInActive($data)
    {
        $subscriptionHandle = $data['subscription'];
        $customerHandle = $data['customer'];
        /** @var \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription */
        $customerSubscription = $this->customerSubscriptionFactory->create();
        $customerSubscription->load($subscriptionHandle, 'subscription_handle');
        if ($customerSubscription->getEntityId()) {
            $customerSubscription->setStatus('in_active');
            try {
                $this->customerSubscriptionRepository->save($customerSubscription);
                $this->updateCustomerSubscriber($customerHandle);
                return [
                    'status' => 200,
                    'message' => 'update status already',
                ];
            } catch (LocalizedException $e) {
                return [
                    'status' => 500,
                    'message' => 'settled webhook exception : ' . $e->getMessage(),
                ];
            }
        }
        return [
            'status' => 500,
            'message' => 'Not found subscription'
        ];
    }
}
