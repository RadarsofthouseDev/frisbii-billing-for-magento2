<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Radarsofthouse\BillwerkPlusSubscription\Client\Api;
use Throwable;

class Subscription extends AbstractHelper
{
    public const ENDPOINT = 'subscription';

    /**
     * @var Api|null
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
        $this->client = new Api();
        $this->logger = $logger;
    }

    /**
     *  Get planlist.
     *
     * @param string $apiKey
     * @param null|string $customerHandle
     * @param null|string $nextPageToken
     * @return array
     */
    public function getList(string $apiKey, $customerHandle = null, $nextPageToken = null)
    {
        $result = [];
        try {
            $param = [
                'size' => 100,
                'from' => "1970-01-01",
            ];
            if (null !== $customerHandle) {
                $param['customer'] = $customerHandle;
            }
            if (null !== $nextPageToken) {
                $param['next_page_token'] = $nextPageToken;
            }

            $response = $this->client->get($apiKey, 'list/subscription', $param);
            if ($this->client->success() && array_key_exists('next_page_token', $response)) {
                $result = $response['content'];
                $nexPageResult = $this->getList($apiKey, $customerHandle, $response['next_page_token']);
                $result = array_merge($result, $nexPageResult);
            } elseif ($this->client->success() && array_key_exists('count', $response) && (int)$response['count'] > 0) {
                $result = $response['content'];
            }
        } catch (Throwable $e) {
            return [];
        }
        return $result;
    }

    /**
     * Get subscription by handle
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|array
     * @throws Throwable
     */
    public function get($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->get($apiKey, self::ENDPOINT . "/{$handle}");
        if ($this->client->success()) {
            $log['response'] = $response;
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
     * Get subscription metadata by handle
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|array
     * @throws Throwable
     */
    public function getMetadata($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->get($apiKey, self::ENDPOINT . "/$handle/metadata");
        if ($this->client->success()) {
            $log['response'] = $response;
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
     * Create subscription
     *
     * @param string $apiKey
     * @param array $subscription
     * @return bool|array
     * @throws \Exception
     */
    public function create($apiKey, $subscription)
    {
        $log = ['param' => ['subscription' => $subscription]];
        $response = $this->client->post($apiKey, self::ENDPOINT, $subscription);
        if ($this->client->success()) {
            $log['response'] = $response;
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
     * On hold subscription.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onHold($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->post($apiKey, self::ENDPOINT."/$handle/on_hold", ['handle' => $handle]);
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

    /**
     *  Reactive subscription.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function reactivate($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->post($apiKey, self::ENDPOINT."/$handle/reactivate", ['handle' => $handle]);
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

    /**
     *  Cancel subscription.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancel($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->post($apiKey, self::ENDPOINT. "/$handle/cancel", ['handle' => $handle]);
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

    /**
     * Uncanceled subscription.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uncancel($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->post($apiKey, self::ENDPOINT. "/$handle/uncancel", ['handle' => $handle]);
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

    /**
     *  Expire subscription.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function expire($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->post($apiKey, self::ENDPOINT. "/$handle/expire", ['handle' => $handle]);
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

    /**
     * Delete subscription
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->delete($apiKey, self::ENDPOINT. "/$handle", ['handle' => $handle]);
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

    /**
     * Get subscription add on
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $addonHandle
     * @return bool|array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAddon($apiKey, $handle, $addonHandle)
    {
        $log = ['param' => ['handle' => $handle, 'addon_handle' => $addonHandle]];
        $response = $this->client->get($apiKey, self::ENDPOINT . "/$handle/add_on/$addonHandle");
        if ($this->client->success()) {
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            return $response;
        } else {
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addError(__METHOD__, $log, true);
            return false;
        }
    }
}
