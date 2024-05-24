<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Catalog\Product;

class Grid extends \Magento\Catalog\Block\Adminhtml\Product\Grid
{
    /**
     * Add a new column.
     *
     * @return $this|\Magento\Catalog\Block\Adminhtml\Product\Grid|Grid
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn(
            'billwerk_sub_plan',
            [
                'header' => 'Billwerk+ ' . __('Subscription Plan'),
                'index' => 'billwerk_sub_plan',
                'type' => 'varchar',
            ]
        );
        return $this;
    }
}
