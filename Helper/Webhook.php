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
     * @var array
     */
    private $lastError = [];

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
     * Get webhook settings from the provider.
     *
     * @param string $apiKey
     * @return array|false
     */
    public function getSettings(string $apiKey)
    {
        $this->lastError = [];
        $param = [];
        $log = ['param' => $param];
        try {
            $response = $this->client->get($apiKey, self::ENDPOINT, $param);
            $log['response'] = $this->getSafeSettingsLog($response);
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success() && is_array($response)) {
                return $response;
            }
            $this->lastError = $this->getClientError();
        } catch (\Exception $e) {
            $log['exception_error'] = $e->getMessage();
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addInfo(__METHOD__, $log, true);
            $this->lastError = $this->getClientError();
        }
        return false;
    }

    /**
     * Return details about the last failed provider request.
     *
     * @return array
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get configured webhook URLs from the provider.
     *
     * @param string $apiKey
     * @return array|false
     */
    public function getUrl(string $apiKey)
    {
        $settings = $this->getSettings($apiKey);

        return is_array($settings) && isset($settings['urls']) ? $settings['urls'] : false;
    }

    /**
     * Update webhook url
     *
     * @param string $apiKey
     * @return bool
     * @throws GuzzleException
     */
    public function updateUrl(string $apiKey)
    {
        $this->lastError = [];
        try {
            $defaultStore = $this->storeManager->getDefaultStoreView();
            if (!$defaultStore) {
                foreach ($this->storeManager->getStores() as $store) {
                    if ($store->getIsActive()) {
                        $defaultStore = $store;
                        break;
                    }
                }
            }
            if (!$defaultStore) {
                $this->lastError = ['message' => 'No active store found.'];
                $this->logger->addError('No active store found.');
                return false;
            }
            $url = rtrim($defaultStore->getBaseUrl(), '/') . '/billwerkplussubscription/webhooks/index';
        } catch (\Exception $e) {
            $this->lastError = ['message' => $e->getMessage()];
            return false;
        }
        $urls = [$url];
        $currentUrls = $this->getUrl($apiKey);
        if ($currentUrls === false && !empty($this->lastError)) {
            return false;
        }
        if (is_array($currentUrls) && !empty($currentUrls)) {
            $currentUrls = array_values(array_filter($currentUrls, function ($currentUrl) {
                return strpos($currentUrl, '/billwerkplussubscription/webhooks/index') === false;
            }));
            $urls = $currentUrls;
            if (!in_array($url, $currentUrls, true)) {
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
            $log['response'] = $this->getSafeSettingsLog($response);
            $this->logger->addInfo(__METHOD__, $log, true);
            if ($this->client->success() && is_array($response) && isset($response['urls'])) {
                return $response['urls'];
            }
            $this->lastError = $this->getClientError();
        } catch (\Exception $e) {
            $log['exception_error'] = $e->getMessage();
            $log['http_errors'] = $this->client->getHttpError();
            $log['response_errors'] = $this->client->getErrors();
            $this->logger->addInfo(__METHOD__, $log, true);
            $this->lastError = $this->getClientError();
        }
        return false;
    }

    /**
     * Return a safe subset of provider webhook settings for logs.
     *
     * @param mixed $response
     * @return array
     */
    private function getSafeSettingsLog($response)
    {
        if (!is_array($response)) {
            return [];
        }

        return [
            'urls' => isset($response['urls']) && is_array($response['urls']) ? $response['urls'] : [],
            'disabled' => isset($response['disabled']) ? (bool)$response['disabled'] : null,
        ];
    }

    /**
     * Return whitelisted provider error fields.
     *
     * @return array
     */
    private function getClientError()
    {
        $error = [];
        $httpError = $this->client->getHttpError();
        $clientErrors = $this->client->getErrors();
        if (is_scalar($httpError) && (string)$httpError !== '') {
            $error['http_error'] = (string)$httpError;
        }

        foreach (['request_id', 'code', 'error', 'message'] as $key) {
            if (isset($clientErrors[$key]) && is_scalar($clientErrors[$key])) {
                $error[$key] = (string)$clientErrors[$key];
            }
        }

        return $error ?: ['message' => 'Webhook API request failed.'];
    }
}
