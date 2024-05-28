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
        $urls = null;
        $urlsTest = null;
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $apiKey = $this->helper->getConfig('private_key', $storeId);
            $apiKeyTest = $this->helper->getConfig('private_key_test', $storeId);
            $this->logger->addDebug('api key.', ['api_key'=>$apiKey, 'api_key_test'=>$apiKeyTest]);
            if (!empty($apiKey)) {
                $urls = $this->webhookHelper->updateUrl($apiKey);
            }
            if (!empty($apiKeyTest)) {
                $urlsTest = $this->webhookHelper->updateUrl($apiKeyTest);
            }
            $this->logger->addDebug('webhook update result', ['urls'=>$urls, 'urls_test'=>$urlsTest]);
            if (($urls !== false && $urls !== null) || ($urlsTest !== false && $urlsTest !== null)) {
                return $result->setData([
                    'success' => true,
                    'urls' => $urls,
                    'urls_test' => $urlsTest,
                    'time' => $lastUpdateTime
                ]);
            }

        } catch (\Throwable $e) {
            $this->logger->addError($e->getMessage());
        }
        return $result->setData(['success' => false, 'time' => $lastUpdateTime]);
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
