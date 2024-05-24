<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface SessionInterface
{

    public const HANDLE = 'handle';
    public const ORDER_ID = 'order_id';
    public const CREATED = 'created';
    public const SESSION_ID = 'session_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const ORDER_TYPE = 'order_type';

    /**
     * Get session_id
     *
     * @return string|null
     */
    public function getSessionId();

    /**
     * Set session_id
     *
     * @param string $sessionId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setSessionId($sessionId);

    /**
     * Get handle
     *
     * @return string|null
     */
    public function getHandle();

    /**
     * Set handle
     *
     * @param string $handle
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setHandle($handle);

    /**
     * Get order_type
     *
     * @return string|null
     */
    public function getOrderType();

    /**
     * Set order_type
     *
     * @param string $orderType
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setOrderType($orderType);

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
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setOrderId($orderId);

    /**
     * Get order_increment_id
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set order_increment_id
     *
     * @param string $orderIncrementId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get created
     *
     * @return string|null
     */
    public function getCreated();

    /**
     * Set created
     *
     * @param string $created
     * @return \Radarsofthouse\BillwerkPlusSubscription\Session\Api\Data\SessionInterface
     */
    public function setCreated($created);
}
