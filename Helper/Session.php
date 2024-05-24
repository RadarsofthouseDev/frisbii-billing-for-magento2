<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Radarsofthouse\BillwerkPlusSubscription\Client\Checkout;

class Session extends AbstractHelper
{
    public const ENDPOINT = 'session';

    /**
     * @var Checkout|null
     */
    protected $client = null;

    /**
     * @var Logger|null
     */
    protected $logger = null;

    /**
     * @param Context $context
     * @param Logger $logger
     */

    public function __construct(
        Context $context,
        Logger  $logger
    ) {
        parent::__construct($context);
        $this->client = new Checkout();
        $this->logger = $logger;
    }

    /**
     * Create charge session
     *
     * @param string $apiKey
     * @param array $session
     * @return bool|mixed
     * @throws Exception|GuzzleException
     */
    public function chargeCreate(string $apiKey, array $session)
    {
        $log = ['param' => ['session' => $session]];
        $response = $this->client->post($apiKey, self::ENDPOINT . '/charge', $session);
        if ($this->client->success()) {
            $log ['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            return $response;
        } else {
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addError(__METHOD__, $log, true);
            return false;
        }
    }

    /**
     * Create session charge with create exist customer.
     *
     * @param string $apiKey
     * @param string $customerHandle
     * @param array $order
     * @param array $paymentMethods
     * @param bool $settle
     * @param array $option
     * @return bool|array
     * @throws Exception|GuzzleException
     */
    public function chargeCreateWithExistCustomer(
        string $apiKey,
        string $customerHandle,
        array  $order,
        array  $paymentMethods,
        bool   $settle,
        array  $option = []
    ) {
        $order['customer_handle'] = $customerHandle;
        $order['settle'] = $settle;
        $option['order'] = $order;
        $option['settle'] = $settle;
        $option['payment_methods'] = $paymentMethods;
        return $this->chargeCreate($apiKey, $option);
    }

    /**
     * Create session charge with create new customer.
     *
     * @param string $apiKey
     * @param array $customer
     * @param array $order
     * @param array $paymentMethods
     * @param bool $settle
     * @param array $option
     * @return bool|array
     * @throws Exception|GuzzleException
     */
    public function chargeCreateWithNewCustomer(
        string $apiKey,
        array  $customer,
        array  $order,
        array  $paymentMethods,
        bool   $settle,
        array  $option = []
    ) {
        $order['customer'] = $customer;
        $order['settle'] = $settle;
        $option['order'] = $order;
        $option['settle'] = $settle;
        $option['payment_methods'] = $paymentMethods;
        return $this->chargeCreate($apiKey, $option);
    }

    /**
     * Create session subscription with create new customer.
     *
     * @param string $apiKey
     * @param array $session
     * @return bool|mixed
     * @throws GuzzleException
     */
    public function subscriptionCreate(string $apiKey, array $session)
    {
        $log = ['param' => ['session' => $session]];
        $response = $this->client->post($apiKey, self::ENDPOINT . '/subscription', $session);
        if ($this->client->success()) {
            $log ['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            return $response;
        } else {
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addError(__METHOD__, $log, true);
            return false;
        }
    }

    /**
     *  Create session subscription with create exist customer.
     *
     * @param string $apiKey
     * @param string $customerHandle
     * @param array $subscription
     * @param array $paymentMethods
     * @param array $option
     * @return bool|mixed
     * @throws Exception|GuzzleException
     */
    public function subscriptionCreateWithExistCustomer(
        string $apiKey,
        string $customerHandle,
        array  $subscription,
        array  $paymentMethods,
        array  $option = []
    ) {
        $subscription['customer'] = $customerHandle;
        $option['prepare_subscription'] = $subscription;
        $option['payment_methods'] = $paymentMethods;
        return $this->subscriptionCreate($apiKey, $option);
    }

    /**
     * Create session subscription with create new customer.
     *
     * @param string $apiKey
     * @param array $customer
     * @param array $subscription
     * @param array $paymentMethods
     * @param array $option
     * @return bool|array
     * @throws Exception|GuzzleException
     */
    public function subscriptionCreateWithNewCustomer(
        string $apiKey,
        array  $customer,
        array  $subscription,
        array  $paymentMethods,
        array  $option = []
    ) {
        $subscription['create_customer'] = $customer;
        $option['prepare_subscription'] = $subscription;
        $option['payment_methods'] = $paymentMethods;
        return $this->subscriptionCreate($apiKey, $option);
    }

    /**
     * Delete session
     *
     * @param string $apiKey
     * @param string $id
     * @return bool
     * @throws Exception|GuzzleException
     */
    public function delete(string $apiKey, string $id)
    {
        $log = ['param' => ['id' => $id]];
        $response = $this->client->delete($apiKey, self::ENDPOINT . "/$id");
        if ($this->client->success()) {
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            return true;
        } else {
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addError(__METHOD__, $log, true);
            return false;
        }
    }
}
