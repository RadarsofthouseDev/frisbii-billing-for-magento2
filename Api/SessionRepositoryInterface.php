<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SessionRepositoryInterface
{

    /**
     * Save Session
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface $session
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface $session
    );

    /**
     * Retrieve Session
     *
     * @param string $sessionId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($sessionId);

    /**
     * Retrieve Session matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Session
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface $session
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface $session
    );

    /**
     * Delete Session by ID
     *
     * @param string $sessionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($sessionId);
}
