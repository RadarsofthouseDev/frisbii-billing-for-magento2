<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;

class Subscription extends Template implements TabInterface
{

    /** @var Registry */
    protected $coreRegistry;

    /** @var CustomerSubscriberRepositoryInterface $customerSubscriberRepository */
    protected $customerSubscriberRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param array $data
     */
    public function __construct(
        Context                               $context,
        Registry                              $registry,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        array                                 $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerSubscriberRepository = $customerSubscriberRepository;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    public function getTabLabel()
    {
        return __('Billwerk+ Subscription');
    }

    /**
     * @inheritDoc
     */
    public function getTabTitle()
    {
        return __('Billwerk+ Subscription');
    }

    /**
     * @inheritDoc
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTabUrl()
    {
        return $this->getUrl('radarsofthouse_billwerkplussubscription/*/subscription', ['_current' => true]);
    }

    /**
     * @inheritDoc
     */
    public function isAjaxLoaded()
    {
        return true;
//        return false;
    }

    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        if ($this->getCustomerId() && $this->getSubscriberHandle()) {
            return true;
        }
        return false;
    }

    /**
     * Get customer id.
     *
     * @return mixed|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        if ($this->getCustomerId() && $this->getSubscriberHandle()) {
            return false;
        }
        return true;
    }

    /**
     * Get subscription handle.
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
}
