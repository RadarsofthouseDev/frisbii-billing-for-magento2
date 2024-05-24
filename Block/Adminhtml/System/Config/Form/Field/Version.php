<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

class Version extends Field
{
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * Get Magento version as string.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->moduleList->getOne('Radarsofthouse_BillwerkPlusSubscription')['setup_version'];
    }
}
