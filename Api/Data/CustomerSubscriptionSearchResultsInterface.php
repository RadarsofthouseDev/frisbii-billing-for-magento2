<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface CustomerSubscriptionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get CustomerSubscription list.
     *
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
