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
        SessionInterfaceFactory                 $sessionInterfaceFactory
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

        $id = $params['id'] ?? '';

        /** @var \Radarsofthouse\BillwerkPlusSubscription\Model\Session $session */
        $session = $this->sessionInterfaceFactory->create()->load($id, 'handle');
        $orderId = $session->getOrderId();

        $isAjax = 0;
        if (isset($params['_isAjax'])) {
            $isAjax = 1;
        }
        $order = $this->orderInterface->load($orderId);
        $cancelConfig = $this->helper->getConfig('cancel_order_after_payment_cancel', $order->getStoreId());

        $this->logger->addDebug(__METHOD__, [$cancelConfig, $order->canCancel()]);
        if ($cancelConfig && $order->canCancel()) {

            $transactions = $this->transactionSearchResultInterfaceFactory->create()->addOrderIdFilter($order->getId());

            // don't allow the cancellation if already have transactions (payment is paid)
            if (count($transactions->getItems()) == 0) {
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
                $this->checkoutSession->restoreQuote();
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
