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
     *  Get subscription addons.
     * @param $subscription
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionAddOns($subscription)
    {
        $addons = [];
        if (array_key_exists('subscription_add_ons', $subscription)) {
            $apiKey = $this->helper->getApiKey();
            foreach ($subscription['subscription_add_ons'] as $addonHandle) {

                try {
                    $addon =  $this->subscriptionHelper->getAddon($apiKey, $subscription['handle'], $addonHandle);
                    if ($addon) {
                        $addons[] = $addon;
                    }
                } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
        }
        return $addons;
    }

    public function getTotalAddonsAmount($addOns)
    {
        if (!is_array($addOns) || empty($addOns)) {
            return ['amount' => 0, 'currency' => null];
        }
        $totalPrice = 0;
        $currency = null;
        foreach ($addOns as $addOn) {
            if (array_key_exists('currency', $addOn)) {
                $currency = $addOn['currency'];
            }elseif(array_key_exists('add_on', $addOn) && array_key_exists('currency', $addOn['add_on'])) {
                $currency = $addOn['add_on']['currency'];
            }
            if (array_key_exists('quantity', $addOn) && array_key_exists('amount', $addOn)) {
                $totalPrice += ($addOn['quantity'] * $addOn['amount'])/100;
            }elseif (array_key_exists('amount', $addOn)) {
                $totalPrice += $addOn['amount'] / 100;
            }else{
                $totalPrice += $addOn['add_on']['amount'] / 100;
            }
        }
        return [
            'amount' => $totalPrice,
            'currency' => $currency
        ];
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
            return $this->plans[$handle];
        }
        $apiKey = $this->helper->getApiKey();
        $plan = $this->planHelper->get($apiKey, $handle);
        if ($plan) {
            $this->plans[$handle]['price'] = ($plan['amount'] / 100);
            $this->plans[$handle]['name'] = $plan['name'];
            $this->plans[$handle]['currency'] = $plan['currency'] ?? null;
            return $this->plans[$handle];
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
            $this->plans[$handle]['price'] = ($plan['amount'] / 100);
            $this->plans[$handle]['name'] = $plan['name'];
            $this->plans[$handle]['currency'] = $plan['currency']  ?? null;
            return $this->plans[$handle]['name'];
        }
    }

    /**
     * Get subscription addons name.
     *
     * @param $addOns
     * @return string
     */
    public function getAddonsName($addOns)
    {
        if (!is_array($addOns) || empty($addOns)) {
            return '';
        }
        $names = [];
        foreach ($addOns as $addOn) {
            if(array_key_exists('add_on', $addOn) && array_key_exists('name', $addOn['add_on'])) {
                $names[] = $addOn['add_on']['name'];
            }
        }
        return !empty($names) ? ' + ' . implode(',', $names) : '';
    }
}
