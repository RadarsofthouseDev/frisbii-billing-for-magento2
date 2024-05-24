<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface CustomerSubscriptionInterface
{

    public const SUBSCRIPTION_HANDLE = 'subscription_handle';
    public const ENTITY_ID = 'entity_id';
    public const STATUS = 'status';
    public const CUSTOMER_ID = 'customer_id';
    public const ORDER_ID = 'order_id';
    public const CUSTOMER_HANDLE = 'customer_handle';

    /**
     * Get entity_id
     *
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param string $entityId
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
     */
    public function setEntityId($entityId);

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
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
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
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
     */
    public function setCustomerHandle($customerHandle);

    /**
     * Get subscription_handle
     *
     * @return string|null
     */
    public function getSubscriptionHandle();

    /**
     * Set subscription_handle
     *
     * @param string $subscriptionHandle
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
     */
    public function setSubscriptionHandle($subscriptionHandle);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
     */
    public function setStatus($status);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \Radarsofthouse\BillwerkPlusSubscription\CustomerSubscription\Api\Data\CustomerSubscriptionInterface
     */
    public function setOrderId($orderId);
}
