<?php

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Setup\Patch\Data;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class P004EncryptApiKeys implements DataPatchInterface
{
    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EncryptorInterface $encryptor
    ) {
    }

    /**
     * Encrypt legacy plaintext API keys in Magento configuration storage.
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $select = $connection->select()
            ->from($configTable, ['config_id', 'value'])
            ->where('path IN (?)', [
                'payment/billwerkplus_subscription/private_key_test',
                'payment/billwerkplus_subscription/private_key',
            ]);

        $connection->startSetup();
        foreach ($connection->fetchAll($select) as $config) {
            $value = trim((string)$config['value']);
            // Skip empty values and values that are already encrypted
            if ($value === '' || preg_match('/^\d+:\d+:/', $value)) {
                continue;
            }

            $connection->update(
                $configTable,
                ['value' => $this->encryptor->encrypt($value)],
                ['config_id = ?' => (int)$config['config_id']]
            );
        }
        $connection->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
