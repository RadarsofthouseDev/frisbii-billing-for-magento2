<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Plan;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;

class Subscriptions extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var CustomerSubscriberRepositoryInterface
     */
    protected $customerSubscriberRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Subscription
     */
    protected $subscriptionHelper;

    /**
     * @var Plan
     */
    protected $planHelper;

    /**
     * @var string
     */
    private $customerId;

    /**
     * @var array
     */
    private $plans = [];

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param Data $helper
     * @param Subscription $subscriptionHelper
     * @param Plan $planHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        TimezoneInterface $timezoneInterface,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        Data $helper,
        Subscription $subscriptionHelper,
        Plan $planHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->timezoneInterface = $timezoneInterface;
        $this->customerSubscriberRepository =  $customerSubscriberRepository;
        $this->helper =  $helper;
        $this->subscriptionHelper =  $subscriptionHelper;
        $this->planHelper =  $planHelper;
    }

    /**
     * Get customer handle.
     *
     * @return string|null
     */
    public function getCustomerHandle()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        try {
            $customer = $this->customerSubscriberRepository->get($customerId);
            $customerHandle = $customer->getCustomerHandle();
        } catch (LocalizedException $e) {
            $customerHandle = null;
        }
        return $customerHandle;
    }

    /**
     * Get subscription list.
     *
     * @return array
     */
    public function getSubscriptions()
    {
        $apiKey = $this->helper->getApiKey();
        $customerHandle = $this->getCustomerHandle();
        if ($customerHandle) {
            return $this->subscriptionHelper->getList($apiKey, $customerHandle);
        }
        return [];
    }

    /**
     * Convert date format.
     *
     * @param string $date
     * @return string
     */
    public function dateFormat($date)
    {
        $staticDate = substr($date, 0, 10);
        return $this->timezoneInterface->formatDate(
            $staticDate,
            \IntlDateFormatter::MEDIUM,
            false
        );
    }

    /**
     * Get plan price text.
     *
     * @param string $handle
     * @return mixed|void
     */
    public function getPlanPrice($handle)
    {
        if (array_key_exists($handle, $this->plans) && array_key_exists('price', $this->plans[$handle])) {
            return $this->plans[$handle]['price'];
        }
        $apiKey = $this->helper->getApiKey();
        $plan = $this->planHelper->get($apiKey, $handle);
        if ($plan) {
            $this->plans[$handle]['price'] = ($plan['amount'] / 100) .' '. $plan['currency'];
            $this->plans[$handle]['name'] = $plan['name'];
            return $this->plans[$handle]['price'];
        }
    }

    /**
     * Get plan name text.
     *
     * @param string $handle
     * @return mixed|void
     */
    public function getPlanName($handle)
    {
        if (array_key_exists($handle, $this->plans) && array_key_exists('name', $this->plans[$handle])) {
            return $this->plans[$handle]['name'];
        }
        $apiKey = $this->helper->getApiKey();
        $plan = $this->planHelper->get($apiKey, $handle);
        if ($plan) {
            $this->plans[$handle]['price'] = ($plan['amount'] / 100) .' '. $plan['currency'];
            $this->plans[$handle]['name'] = $plan['name'];
            return $this->plans[$handle]['name'];
        }
    }
}
