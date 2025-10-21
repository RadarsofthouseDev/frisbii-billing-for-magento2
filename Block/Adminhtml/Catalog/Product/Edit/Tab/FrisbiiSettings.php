<?php

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;

class FrisbiiSettings extends Widget implements TabInterface
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ModuleList $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ModuleList $moduleList,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * Get table label.
     *
     * @return Phrase|string
     */
    public function getTabLabel(): Phrase|string
    {
        return __('Frisbii Settings');
    }

    /**
     * Get tabel title.
     *
     * @return Phrase|string
     */
    public function getTabTitle(): Phrase|string
    {
        return __('Frisbii Settings');
    }

    /**
     * Can show tab in tabs.
     *
     * @return bool
     */
    public function canShowTab(): bool
    {
        if (($moduleInfo = $this->moduleList->getOne('Radarsofthouse_Reepay')) &&
            !empty($moduleInfo['setup_version']) &&
            version_compare($moduleInfo['setup_version'], '1.2.61', '>')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Tab is hidden or not.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        if (($moduleInfo = $this->moduleList->getOne('Radarsofthouse_Reepay')) &&
            !empty($moduleInfo['setup_version']) &&
            version_compare($moduleInfo['setup_version'], '1.2.61', '>')
        ) {
            return true;
        }
        return false;
    }
}
