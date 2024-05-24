<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Webhook extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Radarsofthouse_BillwerkPlusSubscription::system/config/webhook.phtml';

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('radarsofthouse_billwerkplussubscription/system_config/webhook');
    }

    /**
     *  Generate collect button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'subscription_webhook_button',
                'label' => __('Update Webhook'),
            ]
        );

        return $button->toHtml();
    }
}
