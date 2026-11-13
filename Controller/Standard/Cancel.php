<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Standard;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Session;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;

class Cancel extends Action
{
    /**
     * @var OrderInterface
     */
    private $orderInterface;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Session
     */
    private $sessionHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var TransactionSearchResultInterfaceFactory
     */
    protected $transactionSearchResultInterfaceFactory;

    /**
     * @var SessionInterfaceFactory
     */
    protected $sessionInterfaceFactory;

    /**
     * @var Subscription
     */
    protected $subscriptionHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderInterface $orderInterface
     * @param CheckoutSession $checkoutSession
     * @param Session $sessionHelper
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     * @param Logger $logger
     * @param TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory
     * @param SessionInterfaceFactory $sessionInterfaceFactory
     * @param Subscription $subscriptionHelper
     */
    public function __construct(
        Context                                 $context,
        PageFactory                             $resultPageFactory,
        Http                                    $request,
        OrderInterface                          $orderInterface,
        CheckoutSession                         $checkoutSession,
        Session                                 $sessionHelper,
        Data                                    $helper,
        ScopeConfigInterface                    $scopeConfig,
        JsonFactory                             $resultJsonFactory,
        Logger                                  $logger,
        TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory,
        SessionInterfaceFactory                 $sessionInterfaceFactory,
        Subscription                            $subscriptionHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;
        $this->url = $context->getUrl();
        $this->sessionHelper = $sessionHelper;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->transactionSearchResultInterfaceFactory = $transactionSearchResultInterfaceFactory;
        $this->sessionInterfaceFactory = $sessionInterfaceFactory;
        $this->subscriptionHelper = $subscriptionHelper;

        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Json|\Magento\Framework\Controller\Result\Redirect
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $this->logger->addDebug(__METHOD__, $params);
        if (!empty($params['error'])) {
            if ($params['error'] === "error.session.SESSION_DELETED") {
                return $this->redirect('checkout/cart');
            }
        }
        if (empty($params['invoice']) || empty($params['id'])) {
            return $this->redirect('checkout/cart');
        }

        $isAjax = 0;
        if (isset($params['_isAjax'])) {
            $isAjax = 1;
        }

        $id = is_scalar($params['id']) ? trim((string)$params['id']) : '';
        $invoice = is_scalar($params['invoice']) ? trim((string)$params['invoice']) : '';
        $subscriptionHandle = isset($params['subscription']) && is_scalar($params['subscription'])
            ? trim((string)$params['subscription'])
            : '';
        if ($id === '' || $invoice === '') {
            return $this->rejectCallback($isAjax);
        }

        /** @var \Radarsofthouse\BillwerkPlusSubscription\Model\Session $session */
        $session = $this->sessionInterfaceFactory->create()->load($id, 'handle');
        $orderId = $session->getOrderId();
        if (empty($orderId)) {
            $this->logger->addError('Rejected cancel callback with unknown payment session.');
            return $this->rejectCallback($isAjax);
        }

        $order = $this->orderInterface->load($orderId);
        if (!$order->getId() || !$this->isCallbackBoundToOrder($session, $order, $invoice, $subscriptionHandle)) {
            $this->logger->addError('Rejected unbound payment cancel callback.');
            return $this->rejectCallback($isAjax);
        }

        $cancelConfig = $this->helper->getConfig('cancel_order_after_payment_cancel', $order->getStoreId());

        $this->logger->addDebug(__METHOD__, [$cancelConfig, $order->canCancel()]);
        if ($cancelConfig && $order->canCancel()) {

            $transactions = $this->transactionSearchResultInterfaceFactory->create()->addOrderIdFilter($order->getId());

            // a subscription order has no Magento transaction after the first payment, but Accept
            // stores the subscription handle on the order once the payment is confirmed
            $isSubscriptionConfirmed = (string)$session->getOrderType() === 'subscription_order'
                && !empty($order->getBillwerkSubHandle());

            // don't allow the cancellation if already have transactions (payment is paid)
            if (count($transactions->getItems()) == 0 && !$isSubscriptionConfirmed) {
                $order->cancel();
                $order->addStatusHistoryComment('Frisbii : order have been cancelled by payment page');
                $order->save();
                $this->logger->addDebug('Cancelled order : ' . $orderId);
                $apiKey = $this->helper->getApiKey($order->getStoreId());
                $payment = $order->getPayment();
                $this->helper->setReepayPaymentState($payment, 'cancelled');
                // delete reepay session
                $sessionRes = $this->sessionHelper->delete(
                    $apiKey,
                    $id
                );
                if ($this->checkoutSession->restoreQuote()) {
                    $this->restoreSubscriptionItemPrices();
                }
                $this->checkoutSession->unsLastQuoteId()
                    ->unsLastSuccessQuoteId()
                    ->unsLastOrderId()
                    ->unsLastRealOrderId();
            } else {
                $this->logger->addDebug('The payment is done : ignore cancellation for order ' . $orderId);
            }
        }

        if ($isAjax === 1) {
            $result = [
                'status' => 'success',
                'redirect_url' => $this->url->getUrl('checkout/cart'),
            ];
            return $this->resultJsonFactory->create()
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
                ->setData($result);
        }
        if (!$cancelConfig) {
            return $this->redirect('/');
        }
        return $this->redirect('checkout/cart');
    }

    /**
     * Restore the prices of the restored quote items.
     *
     * After place order the SubmitAllAfter observer zeroes the subscription items on the quote
     * (the subscription is charged by Frisbii). When the quote is restored after a cancel the zero
     * prices must be removed, otherwise the cart shows 0 and the next order has a wrong total.
     *
     * @return void
     */
    private function restoreSubscriptionItemPrices()
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId()) {
                return;
            }
            foreach ($quote->getAllItems() as $item) {
                $customPrice = $item->getCustomPrice();
                if ($customPrice !== null && (float)$customPrice == 0) {
                    $item->setCustomPrice(null);
                    $item->setOriginalCustomPrice(null);
                    $item->setConvertedPrice(null);
                }
            }
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();
        } catch (\Throwable $e) {
            $this->logger->addError('Failed to restore quote item prices: ' . $e->getMessage());
        }
    }

    /**
     * Check that the callback parameters belong to the order stored with the session at place order.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Model\Session $session
     * @param OrderInterface $order
     * @param string $invoice
     * @param string $subscriptionHandle
     * @return bool
     */
    private function isCallbackBoundToOrder($session, $order, $invoice, $subscriptionHandle): bool
    {
        $sessionIncrementId = (string)$session->getOrderIncrementId();
        if ($sessionIncrementId !== '' && $sessionIncrementId !== (string)$order->getIncrementId()) {
            return false;
        }

        $orderType = (string)$session->getOrderType();
        if ($orderType === 'mixed_order') {
            // invoice is the order increment id (charge handle) for mixed orders
            return $invoice === (string)$order->getIncrementId();
        }

        if ($orderType === 'subscription_order') {
            // invoice is a provider invoice handle, so bind through the subscription metadata
            if ($subscriptionHandle === '') {
                return false;
            }
            try {
                $apiKey = $this->helper->getApiKey($order->getStoreId());
                $metadata = $this->subscriptionHelper->getMetadata($apiKey, $subscriptionHandle);
            } catch (\Throwable $exception) {
                return false;
            }

            return is_array($metadata)
                && isset($metadata['magento']['orderId'])
                && (string)$metadata['magento']['orderId'] === (string)$order->getId();
        }

        return false;
    }

    /**
     * Reject a callback without cancelling anything.
     *
     * @param int $isAjax
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function rejectCallback($isAjax)
    {
        if ($isAjax !== 1) {
            return $this->redirect('checkout/cart');
        }

        return $this->resultJsonFactory->create()
            ->setHttpResponseCode(400)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
            ->setData([
                'status' => 'error',
                'message' => 'Invalid payment session.',
            ]);
    }

    /**
     * Redirect
     *
     * @param string $path
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirect($path)
    {
        $resultPage = $this->resultRedirectFactory->create()->setPath($path);
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        return $resultPage;
    }
}
