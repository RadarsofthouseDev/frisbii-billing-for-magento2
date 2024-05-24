<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Standard;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Charge;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Session;

class Error extends Action
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
     * @var Charge
     */
    private $chargeHelper;

    /**
     * @var Session
     */
    private $sessionHelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderInterface $orderInterface
     * @param OrderManagementInterface $orderManagement
     * @param CheckoutSession $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param Charge $chargeHelper
     * @param Session $sessionHelper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context                  $context,
        PageFactory              $resultPageFactory,
        Http                     $request,
        OrderInterface           $orderInterface,
        OrderManagementInterface $orderManagement,
        CheckoutSession          $checkoutSession,
        ScopeConfigInterface     $scopeConfig,
        Charge                   $chargeHelper,
        Session                  $sessionHelper,
        JsonFactory              $resultJsonFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderInterface = $orderInterface;
        $this->orderManagement = $orderManagement;
        $this->checkoutSession = $checkoutSession;
        $this->url = $context->getUrl();
        $this->scopeConfig = $scopeConfig;
        $this->chargeHelper = $chargeHelper;
        $this->sessionHelper = $sessionHelper;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->request->getParams('');
        $orderId = $params['invoice'];
        $id = $params['id'];
        $_isAjax = 0;
        if (isset($params['_isAjax'])) {
            $_isAjax = 1;
        }

        if (empty($params['invoice']) || empty($params['id'])) {
            return;
        }
    }
}
