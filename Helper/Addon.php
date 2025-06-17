<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Client\Api;
use Throwable;

class Addon extends AbstractHelper
{
    public const ENDPOINT = 'add_on';

    /**
     * @var Api|null
     */
    protected $client = null;

    /**
     * @var Logger|null
     */
    protected $logger = null;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Logger  $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->client = new Api();
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     *  Get planlist.
     *
     * @param string $apiKey
     * @param null|string $nextPageToken
     * @return array
     */
    public function getList(string $apiKey, $nextPageToken = null)
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
            $response = $this->client->get($apiKey, 'list/'.self::ENDPOINT, $param);
            if ($this->client->success() && array_key_exists('next_page_token', $response)) {
                $result = $response['content'];
                $nexPageResult = $this->getList($apiKey, $response['next_page_token']);
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
     * Get plan.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
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
}
