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
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class Customer extends AbstractHelper
{
    public const ENDPOINT = 'customer';

    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Client\Api
     */
    private $client = null;

    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger
     */
    private $logger = null;

    /**
     *  Constructor
     *
     * @param Context $context
     * @param \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger $logger
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
     * Get customer by email.
     *
     * @param string $apiKey
     * @param string $email
     * @return false|string
     */
    public function search($apiKey, $email)
    {
        $log = ['param' => ['email' => $email]];
        if (empty($email)) {
            $log['input_error'] = 'empty email.';
            $this->logger->addInfo(__METHOD__, $log, true);
            return false;
        }
        $param = [
            'size' => 10,
            'range' => 'created',
            'from' => '1970-01-01',
            'email' => "$email",
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
}
