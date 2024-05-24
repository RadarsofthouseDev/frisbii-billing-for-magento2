<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Customer\Edit\Tab\View;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription as HelperSubscription;

class Subscription extends \Magento\Backend\Block\Template
{
    /** @var Registry $coreRegistry */
    protected $coreRegistry;

    /** @var CustomerSubscriberRepositoryInterface $customerSubscriberRepository */
    protected $customerSubscriberRepository;

    /** @var HelperData */
    protected $helper;

    /** @var HelperSubscription */
    protected $subscriptionHelper;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param HelperData $helper
     * @param HelperSubscription $subscriptionHelper
     * @param array $data
     */
    public function __construct(
        Context                               $context,
        Registry                              $coreRegistry,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        HelperData                            $helper,
        HelperSubscription                    $subscriptionHelper,
        array                                 $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->customerSubscriberRepository = $customerSubscriberRepository;
        $this->helper = $helper;
        $this->subscriptionHelper = $subscriptionHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get customer id
     *
     * @return mixed|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get Billwerk customer handle.
     *
     * @return string|null
     */
    public function getSubscriberHandle()
    {
        try {
            $customerSubscriber = $this->customerSubscriberRepository->get($this->getCustomerId());
            $subscriberHandle = $customerSubscriber->getCustomerHandle();
        } catch (LocalizedException $e) {
            $subscriberHandle = null;
        }
        return $subscriberHandle;
    }

    /**
     * Get Subscription list.
     *
     * @return array|null
     */
    public function getSubscriptions()
    {
        try {
            $apiKey = $this->helper->getApiKey();
            $subscriberHandle = $this->getSubscriberHandle();
            $subscriptions = $this->subscriptionHelper->getList($apiKey, $subscriberHandle);
        } catch (\Throwable $e) {
            $subscriptions = null;
        }
        return $subscriptions;
    }
}
