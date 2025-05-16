<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Customer;

use IntlDateFormatter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\PaymentMethod;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Plan;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Subscription;
use Throwable;

class Subscriptionview extends Template
{

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var FormKey
     */
    protected $formKey;

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
     * @var PaymentMethod
     */
    protected $paymentMethodHelper;

    /**
     * @var Plan
     */
    protected $planHelper;

    /**
     * @var array
     */
    private $subscription = [];

    /**
     * @param Context $context
     * @param Http $request
     * @param FormKey $formKey
     * @param Session $customerSession
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerSubscriberRepositoryInterface $customerSubscriberRepository
     * @param Data $helper
     * @param Subscription $subscriptionHelper
     * @param PaymentMethod $paymentMethodHelper
     * @param Plan $planHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Http $request,
        FormKey $formKey,
        Session $customerSession,
        TimezoneInterface $timezoneInterface,
        CustomerSubscriberRepositoryInterface $customerSubscriberRepository,
        Data $helper,
        Subscription $subscriptionHelper,
        PaymentMethod $paymentMethodHelper,
        Plan $planHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->formKey = $formKey;
        $this->customerSession = $customerSession;
        $this->timezoneInterface = $timezoneInterface;
        $this->customerSubscriberRepository =  $customerSubscriberRepository;
        $this->helper =  $helper;
        $this->subscriptionHelper =  $subscriptionHelper;
        $this->paymentMethodHelper =  $paymentMethodHelper;
        $this->planHelper =  $planHelper;
    }

    /**
     * Get form key.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
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
     * Get subscription.
     *
     * @return array|bool
     * @throws Throwable
     */
    public function getSubscription()
    {
        $handle = $this->request->getParam('handle');
        $apiKey = $this->helper->getApiKey();
        $subscription = $this->subscriptionHelper->get($apiKey, $handle);
        if ($subscription['customer'] === $this->getCustomerHandle()) {
            return $subscription;
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
            IntlDateFormatter::MEDIUM,
            false
        );
    }

    /**
     * Get payment method.
     *
     * @param string $id
     * @return array|bool
     * @throws Throwable
     */
    public function getPaymentMethod($id)
    {
        $apiKey = $this->helper->getApiKey();
        return $this->paymentMethodHelper->get($apiKey, $id);
    }

    /**
     * Get plan name.
     *
     * @param string $handle
     * @return mixed|void
     */
    public function getPlanName($handle)
    {
        $apiKey = $this->helper->getApiKey();
        $plan = $this->planHelper->get($apiKey, $handle);
        if ($plan) {
            return $plan['name'] ?? $handle;
        }
    }

    /**
     * Get config self pause function.
     *
     * @return mixed
     */
    public function enablePause()
    {
        return $this->helper->getConfig('enable_self_pause');
    }

    /**
     * Get config self cancel function.
     *
     * @return mixed
     */
    public function enableCancel()
    {
        return $this->helper->getConfig('enable_self_cancel');
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
