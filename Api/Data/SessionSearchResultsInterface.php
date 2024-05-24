<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface SessionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Session list.
     *
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface[]
     */
    public function getItems();

    /**
     * Set handle list.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
