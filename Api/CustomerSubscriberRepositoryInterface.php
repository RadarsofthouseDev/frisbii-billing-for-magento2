<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CustomerSubscriberRepositoryInterface
{

    /**
     * Save CustomerSubscriber
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber
    );

    /**
     * Retrieve CustomerSubscriber
     *
     * @param string $customerId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($customerId);

    /**
     * Retrieve CustomerSubscriber matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete CustomerSubscriber
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface $customerSubscriber
    );

    /**
     * Delete CustomerSubscriber by ID
     *
     * @param string $customerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerId);
}
