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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Client\Api;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class Webhook extends AbstractHelper
{
    public const ENDPOINT = 'account/webhook_settings';

    /**
     * @var Api
     */
    protected $client;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger $logger
    ) {
        parent::__construct($context);
        $this->client = new Api();
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Update webhook url
     *
     * @param string $apiKey
     * @return bool
     * @throws GuzzleException
     */
    public function getUrl(string $apiKey): bool
    {
        $param = [];
        $log = ['param' => $param];
        try {
            $response = $this->client->get($apiKey, self::ENDPOINT, $param);
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success()) {
                return $response['urls'];
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
     * Update webhook url
     *
     * @param string $apiKey
     * @return bool
     * @throws GuzzleException
     */
    public function updateUrl(string $apiKey): bool
    {
        try {
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);
            $url .= $url[-1] === '/' ? '' : '/';
            $url .= 'billwerkplussubscription/webhooks/index';
        } catch (NoSuchEntityException $e) {
            $log['exception_error'] = $e->getMessage();
            $this->logger->addInfo(__METHOD__, $log, true);
            return false;
        }
        $urls = [$url];
        $currentUrls = $this->getUrl($apiKey);
        if (!empty($currentUrls)) {
            $urls = $currentUrls;
            $isExistUrl = array_search($url, $currentUrls);
            if ($isExistUrl === false) {
                $urls[] = $url;
            }
        }

        $param = [
            'urls' => $urls,
            'disabled' => false,
        ];
        $log = ['param' => $param];
        try {
            $response = $this->client->put($apiKey, self::ENDPOINT, $param);
            $log['response'] = $response;
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success()) {
                return $response['urls'];
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
