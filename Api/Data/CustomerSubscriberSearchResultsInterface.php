<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface CustomerSubscriberSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get CustomerSubscriber list.
     *
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface[]
     */
    public function getItems();

    /**
     * Set customer_handle list.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
