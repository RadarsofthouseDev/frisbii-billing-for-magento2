<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\Customer\Grid;

use Magento\Customer\Model\ResourceModel\Grid\Collection as OriginalCollection;

class Collection extends OriginalCollection
{
    /**
     * Init Select
     *
     * @return $this|OriginalCollection|Collection|void\
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['billwerk_table' => $this->getTable('radarsofthouse_billwerkplus_customer_subscriber')],
            'main_table.entity_id = billwerk_table.customer_id',
            ['customer_handle', 'subscription_active']
        );

//        $this->addFilterToMap('billwerk_customer_handle', 'billwerk_table.customer_handle');
//        $this->addFilterToMap('customer_handle', 'billwerk_table.customer_handle');

        return $this;
    }

    /**
     * Add Field To Filter
     *
     * @param string $field
     * @param string $condition
     * @return OriginalCollection|Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'billwerk_customer_handle') {
            $field = 'billwerk_table.customer_handle';
        } elseif ($field === 'subscription_active') {
            $field = 'billwerk_table.subscription_active';
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add Order
     *
     * @param string $field
     * @param string $direction
     * @return Collection
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        if ($field === 'billwerk_customer_handle') {
            $field = 'billwerk_table.customer_handle';
        } elseif ($field === 'subscription_active') {
            $field = 'billwerk_table.subscription_active';
        }

        return parent::addOrder($field, $direction);
    }

    /**
     * Set Order
     *
     * @param string $field
     * @param string $direction
     * @return Collection
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        if ($field === 'billwerk_customer_handle') {
            $field = 'billwerk_table.customer_handle';
        } elseif ($field === 'subscription_active') {
            $field = 'billwerk_table.subscription_active';
        }

        return parent::setOrder($field, $direction);
    }
}
