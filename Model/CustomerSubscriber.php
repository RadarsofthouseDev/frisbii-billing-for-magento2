<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Model\AbstractModel;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface;

class CustomerSubscriber extends AbstractModel implements CustomerSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscriber::class);
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
    public function getSubscriptionActive()
    {
        return $this->getData(self::SUBSCRIPTION_ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setSubscriptionActive($subscriptionActive)
    {
        return $this->setData(self::SUBSCRIPTION_ACTIVE, $subscriptionActive);
    }
}
