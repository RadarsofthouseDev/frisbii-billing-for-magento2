<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface CustomerSubscriberInterface
{

    public const CUSTOMER_ID = 'customer_id';
    public const SUBSCRIPTION_ACTIVE = 'subscription_active';
    public const CUSTOMER_HANDLE = 'customer_handle';

    /**
     * Get customer_id
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param string $customerId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get customer_handle
     *
     * @return string|null
     */
    public function getCustomerHandle();

    /**
     * Set customer_handle
     *
     * @param string $customerHandle
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface
     */
    public function setCustomerHandle($customerHandle);

    /**
     * Get active_amount
     *
     * @return string|null
     */
    public function getSubscriptionActive();

    /**
     * Set active_amount
     *
     * @param string $subscriptionActive
     * @return \Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface
     */
    public function setSubscriptionActive($subscriptionActive);
}
