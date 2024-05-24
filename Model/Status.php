<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Model\AbstractModel;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\StatusInterface;

class Status extends AbstractModel implements StatusInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\Status::class);
    }

    /**
     * @inheritDoc
     */
    public function getStatusId()
    {
        return $this->getData(self::STATUS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStatusId($statusId)
    {
        return $this->setData(self::STATUS_ID, $statusId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderType()
    {
        return $this->getData(self::ORDER_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setOrderType($orderType)
    {
        return $this->setData(self::ORDER_TYPE, $orderType);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getMaskedCardNumber()
    {
        return $this->getData(self::MASKED_CARD_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setMaskedCardNumber($maskedCardNumber)
    {
        return $this->setData(self::MASKED_CARD_NUMBER, $maskedCardNumber);
    }

    /**
     * @inheritDoc
     */
    public function getFingerprint()
    {
        return $this->getData(self::FINGERPRINT);
    }

    /**
     * @inheritDoc
     */
    public function setFingerprint($fingerprint)
    {
        return $this->setData(self::FINGERPRINT, $fingerprint);
    }

    /**
     * @inheritDoc
     */
    public function getCardType()
    {
        return $this->getData(self::CARD_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setCardType($cardType)
    {
        return $this->setData(self::CARD_TYPE, $cardType);
    }

    /**
     * @inheritDoc
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }
}
