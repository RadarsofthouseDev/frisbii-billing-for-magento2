<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\System\Config;

class WebhookSecret extends Webhook
{
    /**
     * @var string
     */
    protected $_template = 'Radarsofthouse_BillwerkPlusSubscription::system/config/webhook_secret.phtml';

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('radarsofthouse_billwerkplussubscription/system_config/webhookSecret');
    }

    /**
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'subscription_webhook_secret_button',
                'label' => __('Sync Webhook Secrets'),
            ]
        );

        return $button->toHtml();
    }
}
