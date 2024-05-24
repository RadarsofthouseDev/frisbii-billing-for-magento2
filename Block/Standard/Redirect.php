<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Standard;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Theme\Block\Html\Header\Logo;

class Redirect extends Template
{
    /**
     * @var string
     */
    protected $paymentTransactionId;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Logo
     */
    protected $logo;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Database
     */
    protected $fileStorageHelper;

    /**
     * @var string
     */
    protected $logoUrl;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Logo $logo
     * @param UrlInterface $urlInterface
     * @param Database $fileStorageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Logo $logo,
        UrlInterface $urlInterface,
        Database $fileStorageHelper,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->logo = $logo;
        $this->scopeConfig = $context->getScopeConfig();
        $this->urlInterface = $urlInterface;
        $this->fileStorageHelper = $fileStorageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Load order fron checkout session
     *
     * @return false|Order
     */
    public function getOrder()
    {
        if ($this->checkoutSession->getLastRealOrderId()) {
            return $this->orderFactory->create()->loadByIncrementId(
                $this->checkoutSession->getLastRealOrderId()
            );
        }
        return false;
    }

    /**
     *  Set website logo
     *
     * @param string $logoUrl
     * @return $this
     */
    public function setLogoUrl($logoUrl)
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    /**
     * Get website logo
     *
     * @return string
     * @throws ValidatorException
     */
    public function getLogoSrc()
    {
        if (!empty($this->logoUrl) && $this->_isFile($this->logoUrl)) {
            return $this->urlInterface->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . $this->logoUrl;
        }
        return $this->logo->getLogoSrc();
    }

    /**
     * Get website logo Alt
     *
     * @return string
     */
    public function getLogoAlt()
    {
        return $this->logo->getLogoAlt();
    }

    /**
     * Set Payment Transaction Id
     *
     * @param string $paymentTransactionId
     * @return void
     */
    public function setPaymentTransactionId($paymentTransactionId)
    {
        $this->paymentTransactionId = $paymentTransactionId;
    }

    /**
     * Get Payment Transaction Id
     *
     * @return string
     */
    public function getPaymentTransactionId()
    {
        return $this->paymentTransactionId;
    }

    /**
     * Get payment success callback url
     *
     * @return string
     */
    public function getAcceptUrl()
    {
        return $this->urlInterface->getUrl('billwerkplussubscription/standard/accept');
    }

    /**
     * Get payment error callback url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->urlInterface->getUrl('billwerkplussubscription/standard/error');
    }

    /**
     * Get payment cancel callback url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->urlInterface->getUrl('billwerkplussubscription/standard/cancel');
    }

    /**
     * Check file exist.
     *
     * @param string $filename
     * @return bool
     * @throws ValidatorException
     */
    protected function _isFile($filename)
    {
        if ($this->fileStorageHelper->checkDbUsage() && !$this->getMediaDirectory()->isFile($filename)) {
            $this->fileStorageHelper->saveFileToFilesystem($filename);
        }

        return $this->getMediaDirectory()->isFile($filename);
    }
}
