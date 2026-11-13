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
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Webhook as WebhookHelper;

class WebhookSecret extends Action
{
    public const ADMIN_RESOURCE = 'Radarsofthouse_BillwerkPlusSubscription::config_billwerkplus_subscription';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var WebhookHelper
     */
    private $webhookHelper;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        TimezoneInterface $timezone,
        WebhookHelper $webhookHelper,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->cacheTypeList = $cacheTypeList;
        $this->encryptor = $encryptor;
        $this->timezone = $timezone;
        $this->webhookHelper = $webhookHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $lastUpdateTime = $this->timezone->formatDate(null, IntlDateFormatter::MEDIUM, true);

        if (!$this->getRequest()->isPost()) {
            return $result->setHttpResponseCode(405)
                ->setHeader('Allow', 'POST', true)
                ->setData([
                    'success' => false,
                    'message' => 'Only POST requests are allowed.',
                    'time' => $lastUpdateTime,
                ]);
        }

        try {
            $apiKeyData = $this->getApiKeyData();
            $synced = [];
            $failed = [];
            $skipped = $apiKeyData['skipped'];
            $removed = [];
            $cleanupFailed = false;
            $accounts = [];

            foreach ($apiKeyData['entries'] as $entry) {
                $fingerprint = hash('sha256', $entry['api_key']);
                if (!isset($accounts[$fingerprint])) {
                    $accounts[$fingerprint] = [
                        'api_key' => $entry['api_key'],
                        'entries' => [],
                    ];
                }
                $accounts[$fingerprint]['entries'][] = $entry;
            }

            $connection = $this->resourceConnection->getConnection();
            $configTable = $this->resourceConnection->getTableName('core_config_data');

            foreach ($accounts as $account) {
                $settings = $this->webhookHelper->getSettings($account['api_key']);
                $secret = is_array($settings) && isset($settings['secret']) && is_scalar($settings['secret'])
                    ? trim((string)$settings['secret'])
                    : '';

                if ($secret === '') {
                    foreach ($account['entries'] as $entry) {
                        $failed[] = array_merge(
                            $this->getReference($entry),
                            ['reason' => 'Webhook secret could not be retrieved.']
                        );
                    }
                    continue;
                }

                try {
                    $accountSynced = [];
                    $connection->beginTransaction();
                    foreach ($account['entries'] as $entry) {
                        $this->saveSecret($connection, $configTable, $entry, $secret);
                        $accountSynced[] = $this->getReference($entry);
                    }
                    $connection->commit();
                    $synced = array_merge($synced, $accountSynced);
                } catch (\Exception $exception) {
                    $connection->rollBack();
                    foreach ($account['entries'] as $entry) {
                        $failed[] = array_merge(
                            $this->getReference($entry),
                            ['reason' => 'Webhook secret could not be saved.']
                        );
                    }
                    $this->logger->addError('Failed to save synchronized webhook secret.', [
                        'scope_count' => count($account['entries']),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            try {
                $connection->beginTransaction();
                $removed = $this->removeOrphanedSecrets(
                    $connection,
                    $configTable,
                    $apiKeyData['direct_keys']
                );
                $connection->commit();
            } catch (\Exception $exception) {
                $connection->rollBack();
                $cleanupFailed = true;
                $this->logger->addError('Failed to remove orphaned webhook secrets.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            if (!empty($synced) || !empty($removed)) {
                $this->cacheTypeList->cleanType('config');
            }

            $success = !$cleanupFailed && empty($failed) && (empty($skipped) || !empty($synced));
            if ($cleanupFailed) {
                $message = 'Some orphaned webhook secrets could not be removed.';
            } elseif (empty($apiKeyData['entries']) && empty($skipped)) {
                $message = empty($removed)
                    ? 'No directly configured API keys were found.'
                    : 'Orphaned secrets removed. No directly configured API keys were found.';
            } elseif (empty($apiKeyData['entries'])) {
                $message = 'No usable directly configured API keys were found.';
            } elseif ($success && !empty($skipped)) {
                $message = 'Webhook secrets synchronized. Some API keys were skipped.';
            } else {
                $message = $success
                    ? 'Webhook secrets synchronized.'
                    : 'Some webhook secrets could not be synchronized.';
            }

            $this->logger->addInfo('Synchronized webhook secrets.', [
                'synced' => $synced,
                'failed' => $failed,
                'skipped' => $skipped,
                'removed' => $removed,
            ]);

            return $result->setData([
                'success' => $success,
                'message' => $message,
                'synced' => $synced,
                'failed' => $failed,
                'skipped' => $skipped,
                'removed' => $removed,
                'time' => $lastUpdateTime,
            ]);
        } catch (\Exception $exception) {
            $this->logger->addError('Failed to synchronize webhook secrets.', [
                'error' => $exception->getMessage(),
            ]);

            return $result->setData([
                'success' => false,
                'message' => 'Webhook secret synchronization failed.',
                'time' => $lastUpdateTime,
            ]);
        }
    }

    /**
     * Read directly configured API keys and their matching secret paths.
     *
     * @return array
     */
    private function getApiKeyData()
    {
        $keyPaths = [
            'payment/billwerkplus_subscription/private_key' => [
                'key_type' => 'private_key',
                'secret_path' => 'payment/billwerkplus_subscription/webhook_secret',
            ],
            'payment/billwerkplus_subscription/private_key_test' => [
                'key_type' => 'private_key_test',
                'secret_path' => 'payment/billwerkplus_subscription/webhook_secret_test',
            ],
        ];
        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');
        $select = $connection->select()
            ->from($configTable, ['scope', 'scope_id', 'path', 'value'])
            ->where('path IN (?)', array_keys($keyPaths));

        $entries = [];
        $skipped = [];
        $directKeys = [];
        foreach ($connection->fetchAll($select) as $config) {
            $keyPath = (string)$config['path'];
            $keyInfo = $keyPaths[$keyPath];
            $entry = [
                'scope' => (string)$config['scope'],
                'scope_id' => (int)$config['scope_id'],
                'key_type' => $keyInfo['key_type'],
                'secret_path' => $keyInfo['secret_path'],
            ];
            $directKeys[$this->getConfigKey(
                $entry['scope'],
                $entry['scope_id'],
                $entry['secret_path']
            )] = true;
            $apiKey = $this->decryptConfigValue($config['value']);
            if ($apiKey === '') {
                $skipped[] = array_merge(
                    $this->getReference($entry),
                    ['reason' => 'API key is empty or could not be decrypted.']
                );
                continue;
            }

            $entry['api_key'] = $apiKey;
            $entries[] = $entry;
        }

        return [
            'entries' => $entries,
            'skipped' => $skipped,
            'direct_keys' => $directKeys,
        ];
    }

    /**
     * Remove secret rows whose matching API key is no longer directly configured.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $configTable
     * @param array $directKeys
     * @return array
     */
    private function removeOrphanedSecrets($connection, $configTable, array $directKeys)
    {
        $secretPaths = [
            'payment/billwerkplus_subscription/webhook_secret' => 'private_key',
            'payment/billwerkplus_subscription/webhook_secret_test' => 'private_key_test',
        ];
        $select = $connection->select()
            ->from($configTable, ['config_id', 'scope', 'scope_id', 'path'])
            ->where('path IN (?)', array_keys($secretPaths));

        $removed = [];
        foreach ($connection->fetchAll($select) as $config) {
            $scope = (string)$config['scope'];
            $scopeId = (int)$config['scope_id'];
            $path = (string)$config['path'];
            if (isset($directKeys[$this->getConfigKey($scope, $scopeId, $path)])) {
                continue;
            }

            $connection->delete($configTable, ['config_id = ?' => (int)$config['config_id']]);
            $removed[] = [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'key_type' => $secretPaths[$path],
            ];
        }

        return $removed;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function decryptConfigValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d+:\d+:/', $value)) {
            try {
                return trim((string)$this->encryptor->decrypt($value));
            } catch (\Exception $exception) {
                return '';
            }
        }

        return $value;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $configTable
     * @param array $entry
     * @param string $secret
     * @return void
     */
    private function saveSecret($connection, $configTable, array $entry, $secret)
    {
        $encryptedSecret = $this->encryptor->encrypt($secret);
        if (!is_string($encryptedSecret) || $encryptedSecret === '') {
            throw new \RuntimeException('Webhook secret encryption failed.');
        }

        $select = $connection->select()
            ->from($configTable, ['config_id'])
            ->where('scope = ?', $entry['scope'])
            ->where('scope_id = ?', $entry['scope_id'])
            ->where('path = ?', $entry['secret_path']);
        $configId = $connection->fetchOne($select);
        if ($configId) {
            $connection->update(
                $configTable,
                ['value' => $encryptedSecret],
                ['config_id = ?' => (int)$configId]
            );
            return;
        }

        $connection->insert($configTable, [
            'scope' => $entry['scope'],
            'scope_id' => $entry['scope_id'],
            'path' => $entry['secret_path'],
            'value' => $encryptedSecret,
        ]);
    }

    /**
     * @param array $entry
     * @return array
     */
    private function getReference(array $entry)
    {
        return [
            'scope' => $entry['scope'],
            'scope_id' => $entry['scope_id'],
            'key_type' => $entry['key_type'],
        ];
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @param string $path
     * @return string
     */
    private function getConfigKey($scope, $scopeId, $path)
    {
        return $scope . ':' . (int)$scopeId . ':' . $path;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization
            ->isAllowed('Radarsofthouse_BillwerkPlusSubscription::config_billwerkplus_subscription');
    }
}
