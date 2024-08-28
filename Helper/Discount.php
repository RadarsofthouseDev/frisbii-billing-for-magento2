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

class Discount extends AbstractHelper
{
    public const ENDPOINT = 'discount';

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
     * Search discount by handle.
     *
     * @param string $apiKey
     * @param string $handle
     * @return false|string
     * @throws GuzzleException
     */
    public function search($apiKey, $handle)
    {
        $log = ['param' => ['handle' => $handle]];
        if (empty($email)) {
            $log['input_error'] = 'empty email.';
            $this->logger->addInfo(__METHOD__, $log, true);
            return false;
        }
        $param = [
            'size' => 10,
            'range' => 'created',
            'from' => '1970-01-01',
            'state' => 'active',
            'handle' => "$handle",
        ];
        try {
            $response = $this->client->get($apiKey, 'list/' . self::ENDPOINT, $param);
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success() && array_key_exists('count', $response) && (int)$response['count'] > 0) {
                foreach ($response['content'] as $index => $item) {
                    if (!array_key_exists('deleted', $item) || empty($item['deleted'])) {
                        return $item['handle'];
                    }
                }
            }
        } catch (\Exception $e) {
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
        $response = $this->client->get($apiKey, self::ENDPOINT . "/$handle");
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
