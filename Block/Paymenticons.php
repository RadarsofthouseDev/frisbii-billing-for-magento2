<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Paymenticons extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
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

        $paymentIcons = explode(',', $iconsConfig);

        return $paymentIcons;
    }
}
