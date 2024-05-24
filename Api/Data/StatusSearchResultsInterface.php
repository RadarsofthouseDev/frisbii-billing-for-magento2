<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface StatusSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Status list.
     *
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface[]
     */
    public function getItems();

    /**
     * Set order_id list.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
