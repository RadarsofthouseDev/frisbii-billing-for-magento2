<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Constructor
     *
     * @param LayoutInterface $layout
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LayoutInterface $layout,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_layout = $layout;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Provide payment icons html
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $store_id = $this->_storeManager->getStore()->getId();
        return [
            'billwerkplus_subscription_payment_icons' => $this->_layout
                ->createBlock(\Radarsofthouse\BillwerkPlusSubscription\Block\Paymenticons::class)
                ->setTemplate('Radarsofthouse_BillwerkPlusSubscription::payment_icons.phtml')->toHtml(),
            "billwerkplus_subscription_instructions" => $this->_scopeConfig->getValue(
                'payment/billwerkplus_subscription/instructions',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store_id
            ),
        ];
    }
}
