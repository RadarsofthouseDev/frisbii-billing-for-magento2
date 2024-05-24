<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Store\Model\StoreManagerInterface;

class Email extends AbstractHelper
{
    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var OrderInterface
     */
    protected $_orderInterface;

    /**
     * @var OrderSender
     */
    protected $_orderSender;
    /**
     * @var Renderer
     */
    protected $addressRenderer;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * Constructor
     *
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param OrderInterface $orderInterface
     * @param OrderSender $orderSender
     * @param Renderer $addressRenderer
     * @param PaymentHelper $paymentHelper
     * @param OrderResource $orderResource
     */
    public function __construct(
        Context               $context,
        TransportBuilder      $transportBuilder,
        Data                  $helper,
        ScopeConfigInterface  $scopeConfig,
        Logger                $logger,
        StoreManagerInterface $storeManager,
        OrderInterface        $orderInterface,
        OrderSender           $orderSender,
        Renderer              $addressRenderer,
        PaymentHelper         $paymentHelper,
        OrderResource         $orderResource
    ) {
        parent::__construct($context);
        $this->_transportBuilder = $transportBuilder;
        $this->_helper = $helper;
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_orderInterface = $orderInterface;
        $this->_orderSender = $orderSender;
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->orderResource = $orderResource;
    }

    /**
     *  Render shipping address into html.
     *
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * Render billing address into html.
     *
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * Render payment into html.
     *
     * @param Order $order
     * @return string
     * @throws Exception
     */
    protected function getPaymentHtml(Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $order->getStore()->getStoreId()
        );
    }

    /**
     * Send order confirmation email.
     *
     * @param string $orderIncrementId
     * @return void
     * @throws Exception
     */
    public function sendOrderConfirmationEmail($orderIncrementId)
    {
        $this->_logger->addDebug(__METHOD__, ['orderIncrementId' => $orderIncrementId]);
        $order = $this->_orderInterface->loadByIncrementId($orderIncrementId);
        if (!$order->getId()) {
            $this->_logger->addDebug('order not found.');
            return;
        }
        if ($this->_helper->getConfig('send_order_email_when_success', $order->getStoreId())) {
            $emailTemplateId = $this->_helper->getConfig('order_confirmation_email_template', $order->getStoreId());

            if (empty($emailTemplateId)) {
                $emailTemplateId = "billwerk_subscription_order_confirmation";
            }

            $senderName = $this->_scopeConfig->getValue(
                'trans_email/ident_sales/name',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            $senderEmail = $this->_scopeConfig->getValue(
                'trans_email/ident_sales/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            $templateVars = [
                'order' => $order,
                'order_id' => $order->getId(),
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'created_at_formatted' => $order->getCreatedAtFormatted(2),
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => $order->getIsNotVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote(),
                    'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];

            $this->_logger->addDebug('$senderName : ' . $senderName);
            $this->_logger->addDebug('$senderEmail : ' . $senderEmail);

            try {

                $transport = $this->_transportBuilder->setTemplateIdentifier($emailTemplateId)
                    ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $order->getStoreId(),
                        ]
                    )
                    ->setTemplateVars($templateVars)
                    ->setFrom(
                        [
                            'name' => $senderName,
                            'email' => $senderEmail
                        ]
                    )
                    ->addTo(
                        $order->getCustomerEmail(),
                        $order->getCustomerName()
                    )
                    ->getTransport();
                try {
                    $transport->sendMessage();
                    $historyItem = $order->addCommentToStatusHistory(
                        __('Sent order confirmation email to customer')
                    );
                    $historyItem->setIsCustomerNotified(true)->save();
                    $order->setEmailSent(true);
                } catch (Exception $e) {
                    $historyItem = $order->addCommentToStatusHistory(
                        __('Send order confirmation email failure: %s', $e->getMessage())
                    );
                    $historyItem->setIsCustomerNotified(false)->save();
                    $order->setEmailSent(null);
                }
                $this->orderResource->saveAttribute($order, ['email_sent']);
            } catch (\Throwable $throwable) {
                $this->_logger->addError($throwable->getMessage());
            }
        }
    }

    /**
     * Send order renewal email.
     *
     * @param string $orderIncrementId
     * @return void
     * @throws Exception
     */
    public function sendOrderRenewalEmail($orderIncrementId)
    {
        $this->_logger->addDebug(__METHOD__, ['orderIncrementId' => $orderIncrementId]);
        $order = $this->_orderInterface->loadByIncrementId($orderIncrementId);
        if (!$order->getId()) {
            $this->_logger->addDebug(__METHOD__, ['orderIncrementId' => $orderIncrementId]);
            return;
        }
        if ($this->_helper->getConfig('send_order_email_when_renewal', $order->getStoreId())) {
            $emailTemplateId = $this->_helper->getConfig('order_renewal_email_template', $order->getStoreId());

            if (empty($emailTemplateId)) {
                $emailTemplateId = "billwerk_subscription_renewal";
            }

            $senderName = $this->_scopeConfig->getValue(
                'trans_email/ident_sales/name',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            $senderEmail = $this->_scopeConfig->getValue(
                'trans_email/ident_sales/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            $templateVars = [
                'order' => $order,
                'order_id' => $order->getId(),
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'created_at_formatted' => $order->getCreatedAtFormatted(2),
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => $order->getIsNotVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote(),
                    'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];

            $this->_logger->addDebug('$senderName : ' . $senderName);
            $this->_logger->addDebug('$senderEmail : ' . $senderEmail);
            try {
                $transport = $this->_transportBuilder->setTemplateIdentifier($emailTemplateId)
                    ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $order->getStoreId(),
                        ]
                    )
                    ->setTemplateVars($templateVars)
                    ->setFrom(
                        [
                            'name' => $senderName,
                            'email' => $senderEmail
                        ]
                    )
                    ->addTo(
                        $order->getCustomerEmail(),
                        $order->getCustomerName()
                    )
                    ->getTransport();
                try {
                    $transport->sendMessage();
                    $historyItem = $order->addCommentToStatusHistory(
                        __('Sent order renewal email to customer')
                    );
                    $historyItem->setIsCustomerNotified(true)->save();
                    $order->setEmailSent(true);
                } catch (Exception $e) {
                    $historyItem = $order->addCommentToStatusHistory(
                        __('Send order renewal email failure: %s', $e->getMessage())
                    );
                    $historyItem->setIsCustomerNotified(false)->save();
                    $order->setEmailSent(null);
                }
                $this->orderResource->saveAttribute($order, ['email_sent']);
            } catch (\Throwable $throwable) {
                $this->_logger->addError($throwable->getMessage());
            }
        }
    }
}
