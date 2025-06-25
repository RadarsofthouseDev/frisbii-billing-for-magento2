<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Paymenticons extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * Get payment icons for credit card payment
     *
     * @return array $paymentIcons
     */
    public function getPaymentIcons()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $iconsConfig = $this->scopeConfig->getValue('payment/billwerkplus_subscription/payment_icons', $storeScope);

        if (empty($iconsConfig)) {
            return [];
        }

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
            $quote = null;
        }

        $paymentIcons = explode(',', $iconsConfig);
        foreach ($paymentIcons as $key => $paymentIcon) {
            if ($paymentIcon === 'vipps_recurring') {
                try {
                    if ($quote === null) {
                        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
                    }else{
                        $currencyCode = $quote->getCurrency()->getQuoteCurrencyCode();
                    }
                    
                    if ($currencyCode == 'NOK') {
                        $paymentIcons[$key] = 'vipps';
                    } elseif (in_array($currencyCode, ['DKK', 'EUR'])) {
                        if (array_search('mobilepay', $paymentIcons) !== false) {
                            unset($paymentIcons[$key]);
                        } else {
                            $paymentIcons[$key] = 'mobilepay';
                        }
                    } else {
                        unset($paymentIcons[$key]);
                    }
                } catch (NoSuchEntityException | LocalizedException $e) {
                    unset($paymentIcons[$key]);
                }
            }
        }

        return $paymentIcons;
    }
}
