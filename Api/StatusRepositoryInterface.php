<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface StatusRepositoryInterface
{

    /**
     * Save Status
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface $status
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface $status
    );

    /**
     * Retrieve Status
     *
     * @param string $statusId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($statusId);

    /**
     * Retrieve Status matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Status
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface $status
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface $status
    );

    /**
     * Delete Status by ID
     *
     * @param string $statusId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($statusId);
}
