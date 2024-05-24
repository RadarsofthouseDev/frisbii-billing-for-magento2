<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Model\AbstractModel;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface;

class CustomerSubscription extends AbstractModel implements CustomerSubscriptionInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscription::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerHandle()
    {
        return $this->getData(self::CUSTOMER_HANDLE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerHandle($customerHandle)
    {
        return $this->setData(self::CUSTOMER_HANDLE, $customerHandle);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionHandle()
    {
        return $this->getData(self::SUBSCRIPTION_HANDLE);
    }

    /**
     * @inheritDoc
     */
    public function setSubscriptionHandle($subscriptionHandle)
    {
        return $this->setData(self::SUBSCRIPTION_HANDLE, $subscriptionHandle);
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
}
