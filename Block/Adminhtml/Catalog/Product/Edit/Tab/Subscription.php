<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Subscription extends Generic implements TabInterface
{

    /**
     * Disable Cache.
     *
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Get table label.
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Billwerk+ Subscription');
    }

    /**
     * Get tabel title.
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Billwerk+ Subscription');
    }

    /**
     * Always show tab.
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Always hidden.
     *
     * @return true
     */
    public function isHidden()
    {
        return true;
    }
}
