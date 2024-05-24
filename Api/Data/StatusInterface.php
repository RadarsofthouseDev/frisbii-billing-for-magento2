<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Api\Data;

interface StatusInterface
{

    public const STATUS_ID = 'status_id';
    public const ORDER_ID = 'order_id';
    public const EMAIL = 'email';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const MASKED_CARD_NUMBER = 'masked_card_number';
    public const CARD_TYPE = 'card_type';
    public const STATUS = 'status';
    public const FINGERPRINT = 'fingerprint';
    public const ORDER_TYPE = 'order_type';

    /**
     * Get status_id
     *
     * @return string|null
     */
    public function getStatusId();

    /**
     * Set status_id
     *
     * @param string $statusId
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setStatusId($statusId);

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
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
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
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setOrderIncrementId($orderIncrementId);

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
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setOrderType($orderType);

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
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setStatus($status);

    /**
     * Get masked_card_number
     *
     * @return string|null
     */
    public function getMaskedCardNumber();

    /**
     * Set masked_card_number
     *
     * @param string $maskedCardNumber
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setMaskedCardNumber($maskedCardNumber);

    /**
     * Get fingerprint
     *
     * @return string|null
     */
    public function getFingerprint();

    /**
     * Set fingerprint
     *
     * @param string $fingerprint
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setFingerprint($fingerprint);

    /**
     * Get card_type
     *
     * @return string|null
     */
    public function getCardType();

    /**
     * Set card_type
     *
     * @param string $cardType
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setCardType($cardType);

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     *
     * @param string $email
     * @return \Radarsofthouse\BillwerkPlusSubscription\Status\Api\Data\StatusInterface
     */
    public function setEmail($email);
}
