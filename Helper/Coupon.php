<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Radarsofthouse\BillwerkPlusSubscription\Client\Api;
use Throwable;

class Coupon extends AbstractHelper
{
    public const ENDPOINT = 'coupon';

    /**
     * @var Api
     */
    private $client = null;

    /**
     * @var Logger
     */
    private $logger = null;

    /**
     *  Constructor
     *
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->client = new Api();
        $this->logger = $logger;
    }

    /**
     *  Get coupon list.
     *
     * @param string $apiKey
     * @param null|string $nextPageToken
     * @return array
     */
    public function list(string $apiKey, $nextPageToken = null)
    {
        $result = [];
        try {
            $param = [
                'size' => 100,
                'from' => "1970-01-01",
                'state' => "active",
            ];
            if (null !== $nextPageToken) {
                $param['next_page_token'] = $nextPageToken;
            }
            $response = $this->client->get($apiKey, 'list/'. self::ENDPOINT, $param);
            if ($this->client->success() && array_key_exists('next_page_token', $response)) {
                $result = $response['content'];
                $nexPageResult = $this->list($apiKey, $response['next_page_token']);
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
     * Search discount by code.
     *
     * @param string $apiKey
     * @param string $code
     * @return false|string
     */
    public function search($apiKey, $code)
    {
        $log = ['param' => ['handle' => $code]];
        if (empty($code)) {
            $log['input_error'] = 'empty code.';
            $this->logger->addInfo(__METHOD__, $log, true);
            return false;
        }
        $param = [
            'size' => 10,
            'range' => 'created',
            'from' => '1970-01-01',
            'state' => 'active',
            'code' => "$code",
        ];
        try {
            $response = $this->client->get($apiKey, 'list/' . self::ENDPOINT, $param);
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success() && array_key_exists('count', $response) && (int)$response['count'] > 0) {
                foreach ($response['content'] as $index => $item) {
                    return $item['handle'];
                }
            }
        } catch (Throwable $e) {
            $log['exception_error'] = $e->getMessage();
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addInfo(__METHOD__, $log, true);
        }
        return false;
    }

    /**
     * Get discount by handle.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|mixed
     * @throws GuzzleException
     */
    public function get($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        $response = $this->client->get($apiKey, self::ENDPOINT . "/$handle/current");
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
     * Validate coupon code.
     *
     * @param string $apiKey
     * @param string $code
     * @param array $optional
     * @return bool|mixed
     * @throws GuzzleException
     */
    public function validate($apiKey, $code, $optional = [])
    {
        $log = ['param' => ['code' => $code]];
        if (empty($code)) {
            $log['input_error'] = 'empty code.';
            $this->logger->addInfo(__METHOD__, $log, true);
            return false;
        }

        $param = ['code' => "$code"];

        if (array_key_exists('plan', $optional) && !empty($optional['plan'])) {
            $param['plan'] = $optional['plan'];
        }

        if (array_key_exists('customer', $optional) && !empty($optional['customer'])) {
            $param['customer'] = $optional['customer'];
        }

        if (array_key_exists('subscription', $optional) && !empty($optional['subscription'])) {
            $param['plan'] = $optional['subscription'];
        }

        $response = $this->client->get($apiKey, self::ENDPOINT . "/code/validate", $param);
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
