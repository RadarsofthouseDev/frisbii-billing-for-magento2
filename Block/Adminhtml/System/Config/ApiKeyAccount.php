<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ApiKeyAccount extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Radarsofthouse_BillwerkPlusSubscription::system/config/api_key_account.phtml';

    /**
     * Remove scope label and identify the key environment.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        $elementId = (string)$element->getId();
        $elementName = (string)$element->getName();
        $this->setData(
            'environment',
            strpos($elementId, 'api_key_account_test') !== false
                || strpos($elementName, 'api_key_account_test') !== false ? 'test' : 'live'
        );

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl(
            'radarsofthouse_billwerkplussubscription/system_config/apiKeyAccount'
        );
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return (string)$this->getData('environment');
    }

    /**
     * @return string
     */
    public function getStatusId()
    {
        return 'billwerkplus-api-key-account-status-' . $this->getEnvironment();
    }
}
