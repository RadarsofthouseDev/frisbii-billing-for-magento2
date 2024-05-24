<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\Email;

class SubscriptionRenewalTemplate implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Email\Template
     */
    private $templateSource;

    /**
     * Constructor
     *
     * @param \Magento\Config\Model\Config\Source\Email\Template $templateSource
     */
    public function __construct(
        \Magento\Config\Model\Config\Source\Email\Template $templateSource
    ) {
        $this->templateSource = $templateSource;
    }

    /**
     * Return list of payment link
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->templateSource->setPath('billwerk_subscription_renewal')->toOptionArray();
    }
}
