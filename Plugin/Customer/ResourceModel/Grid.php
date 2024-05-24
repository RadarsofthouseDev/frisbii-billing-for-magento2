<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Customer\ResourceModel;

class Grid
{
    /** @var string  */
    public static $table = 'customer_grid_flat';

    /** @var string  */
    public static $leftJoinTable = 'radarsofthouse_billwerkplus_customer_subscriber';

    /**
     * After Search
     *
     * @param \Magento\Customer\Model\ResourceModel\Grid\Collection $subject
     * @param \Magento\Framework\Data\Collection $result
     * @return array
     */
    public function afterSearch(
        \Magento\Customer\Model\ResourceModel\Grid\Collection $subject,
        \Magento\Framework\Data\Collection $result
    ) {
        if ($subject->getMainTable() === $subject->getConnection()->getTableName(self::$table)) {
            $leftJoinTableName = $subject->getConnection()->getTableName(self::$leftJoinTable);

            $subject->getSelect()
                ->joinLeft(
                    ['co' => $leftJoinTableName],
                    "co.customer_id = main_table.entity_id",
                    ['customer_handle' => 'co.customer_handle', 'subscription_active' => 'co.subscription_active']
                );

            $where = $subject->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
            $subject->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $where)->group('main_table.entity_id');
        }
        return $result;
    }
}
