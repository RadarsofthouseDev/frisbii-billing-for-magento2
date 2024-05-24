<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Standard;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session\SuccessValidator;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Payment;

class Redirect extends Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Payment $paymentHelper
     * @param Logger $logger
     * @param ProductRepositoryInterface $productRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        Context                                     $context,
        PageFactory                                 $resultPageFactory,
        RedirectFactory                             $resultRedirectFactory,
        ScopeConfigInterface                        $scopeConfig,
        Data                                        $helper,
        Payment                                     $paymentHelper,
        Logger                                      $logger,
        ProductRepositoryInterface                  $productRepository,
        OrderRepositoryInterface                    $orderRepository,
        OrderManagementInterface $orderManagement
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->paymentHelper = $paymentHelper;
        $this->messageManager = $context->getMessageManager();
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->orderManagement = $orderManagement;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Redirect|ResultInterface|Page
     * @throws GuzzleException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        try {
            $this->logger->addDebug(__METHOD__, []);
            if (!$this->_objectManager->get(SuccessValidator::class)->isValid()) {
                return $this->redirect();
            }

            $checkoutOnePageSession = $this->_objectManager->get(Onepage::class)->getCheckout();
            $orderId = $checkoutOnePageSession->getLastOrderId();
            $order = $this->_objectManager->create(Order::class)->load($orderId);

            if (!$order->getId()) {
                return $this->redirect();
            }

            $isMixedOrder = $this->helper->isMixedOrder($order);

            if ($isMixedOrder) {
                $paymentTransactionId = $this->paymentHelper->createCheckoutSession($order);
                if (empty($order->getBillwerkOrderType())) {
                    $order->setBillwerkOrderType('Mixed');
                }
                $this->logger->addDebug('DISPLAY_WINDOW_CHECKOUT');
                $template = 'Radarsofthouse_BillwerkPlusSubscription::standard/window/checkout.phtml';
            } else {
                $paymentTransactionId = $this->paymentHelper->createSubscriptionSession($order);
                if (empty($order->getBillwerkOrderType())) {
                    $order->setBillwerkOrderType('Subscription');
                }
                $this->logger->addDebug('DISPLAY_WINDOW_SUBSCRIPTION');
                $template = 'Radarsofthouse_BillwerkPlusSubscription::standard/window/subscription.phtml';
            }
            $order->save();

            $this->logger->addDebug('$paymentTransactionId : ' . $paymentTransactionId);

            $pageTitleConfig = $this->helper->getConfig('title', $order->getStoreId());
            $resultPage->getConfig()->getTitle()->set($pageTitleConfig);

            $resultPage->getLayout()
                ->getBlock('billwerkplussubscription_standard_redirect')
                ->setTemplate($template)
                ->setLogoUrl($this->getLogoUrl())
                ->setPaymentTransactionId($paymentTransactionId);

        } catch (\Exception $e) {
            $this->logger->addError(__METHOD__ . " Exception : " . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            return $this->redirect();
        }
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        return $resultPage;
    }

    /**
     * Redirect
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirect()
    {
        $resultPage = $this->resultRedirectFactory->create()->setPath('checkout/cart');
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        return $resultPage;
    }

    /**
     * Get logo URL
     *
     * @return string|null
     */
    protected function getLogoUrl()
    {
        $folderName = \Magento\Config\Model\Config\Backend\Image\Logo::UPLOAD_DIR;
        $storeLogoPath = $this->scopeConfig->getValue(
            'design/header/logo_src',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $path = $folderName . '/' . $storeLogoPath;
        if ($storeLogoPath !== null) {
            return $path;
        }
        return null;
    }
}
