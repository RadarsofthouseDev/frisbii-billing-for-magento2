<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Model\AbstractModel;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\SessionInterface;

class Session extends AbstractModel implements SessionInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\Session::class);
    }

    /**
     * @inheritDoc
     */
    public function getSessionId()
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSessionId($sessionId)
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function getHandle()
    {
        return $this->getData(self::HANDLE);
    }

    /**
     * @inheritDoc
     */
    public function setHandle($handle)
    {
        return $this->setData(self::HANDLE, $handle);
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
    public function getCreated()
    {
        return $this->getData(self::CREATED);
    }

    /**
     * @inheritDoc
     */
    public function setCreated($created)
    {
        return $this->setData(self::CREATED, $created);
    }
}
