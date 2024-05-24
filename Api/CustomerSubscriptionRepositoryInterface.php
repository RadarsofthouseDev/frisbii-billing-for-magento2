<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CustomerSubscriptionRepositoryInterface
{

    /**
     * Save CustomerSubscription
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription
    );

    /**
     * Retrieve CustomerSubscription
     *
     * @param string $entityId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($entityId);

    /**
     * Retrieve CustomerSubscription matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete CustomerSubscription
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface $customerSubscription
    );

    /**
     * Delete CustomerSubscription by ID
     *
     * @param string $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
