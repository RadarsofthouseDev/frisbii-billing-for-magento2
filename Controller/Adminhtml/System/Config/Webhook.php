<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Adminhtml\System\Config;

use IntlDateFormatter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as DataHelper;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Webhook as WebhookHelper;

class Webhook extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Http
     */
    protected $http;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var DataHelper
     */
    protected $helper;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param DataHelper $helper
     * @param WebhookHelper $webhookHelper
     * @param TimezoneInterface $timezone
     * @param Logger $logger
     */
    public function __construct(
        Context               $context,
        JsonFactory                  $resultJsonFactory,
        StoreManagerInterface $storeManager,
        DataHelper            $helper,
        WebhookHelper         $webhookHelper,
        TimezoneInterface     $timezone,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->webhookHelper = $webhookHelper;
        $this->timezone = $timezone;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $lastUpdateTime = $this->timezone->formatDate(null, IntlDateFormatter::MEDIUM, true);
        try {
            $apiKeys = [];
            $seenApiKeys = [];
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = $store->getId();
                foreach (['private_key', 'private_key_test'] as $keyType) {
                    $apiKey = $this->helper->getConfiguredApiKey($keyType, $storeId);
                    $fingerprint = $apiKey !== '' ? hash('sha256', $apiKey) : '';
                    if ($fingerprint !== '' && !isset($seenApiKeys[$fingerprint])) {
                        $seenApiKeys[$fingerprint] = true;
                        $apiKeys[] = [
                            'store_id' => $storeId,
                            'key_type' => $keyType,
                            'api_key' => $apiKey,
                        ];
                    }
                }
            }

            $success = [];
            $failed = [];
            foreach ($apiKeys as $apiKeyData) {
                $updatedUrls = $this->webhookHelper->updateUrl($apiKeyData['api_key']);
                $reference = [
                    'store_id' => $apiKeyData['store_id'],
                    'key_type' => $apiKeyData['key_type'],
                ];
                if ($updatedUrls === false) {
                    $failed[] = array_merge($reference, [
                        'error' => $this->webhookHelper->getLastError(),
                    ]);
                } else {
                    $success[] = array_merge($reference, ['urls' => $updatedUrls]);
                }
            }

            if (!empty($failed)) {
                $this->logger->addError('Failed to update webhook URLs.', ['failed_keys' => $failed], true);
                return $result->setData([
                    'success' => false,
                    'message' => 'Unable to update webhook URLs for one or more API keys.',
                    'updated_urls' => $success,
                    'failed_keys' => $failed,
                    'time' => $lastUpdateTime,
                ]);
            }

            $this->logger->addInfo('Updated webhook URLs.', ['updated_urls' => $success]);
            if (!empty($success)) {
                return $result->setData([
                    'success' => true,
                    'updated_urls' => $success,
                    'time' => $lastUpdateTime
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->addError($e->getMessage());
        }
        return $result->setData([
            'success' => false,
            'message' => 'No configured API keys were found.',
            'time' => $lastUpdateTime,
        ]);
    }

    /**
     * Check permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization
            ->isAllowed('Radarsofthouse_BillwerkPlusSubscription::config_billwerkplus_subscription');
    }
}
