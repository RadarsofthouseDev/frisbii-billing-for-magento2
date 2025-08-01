<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Radarsofthouse\BillwerkPlusSubscription\Setup;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @var Config
     */
    private Config $configResource;

    /**
     * Constructor
     *
     * @param Config $configResource
     */
    public function __construct(
        Config $configResource
    ) {
        $this->configResource = $configResource;
    }

    /**
     * Uninstall module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // 1. Drop custom tables
        $this->dropTables($setup);

        // 2. Drop custom columns from existing tables
        $this->dropColumns($setup);

        // 3. Remove configuration data
        $this->removeConfigData();

        // 4. Remove EAV attributes group
        $this->removeEavAttributesGroup($setup);

        $setup->endSetup();
    }

    /**
     * Drop custom tables
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function dropTables(SchemaSetupInterface $setup): void
    {
        $tablesToDrop = [
            'radarsofthouse_billwerkplus_customer_subs',
            'radarsofthouse_billwerkplus_customer_subscriber',
            'radarsofthouse_billwerkplus_session',
            'radarsofthouse_billwerkplus_status',
        ];

        foreach ($tablesToDrop as $tableName) {
            $table = $setup->getTable($tableName);
            if ($setup->getConnection()->isTableExists($table)) {
                $setup->getConnection()->dropTable($table);
            }
        }
    }

    /**
     * Drop custom columns from existing tables
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function dropColumns(SchemaSetupInterface $setup): void
    {

        // Drop columns from sales_order table
        $salesOrderTable = $setup->getTable('sales_order');
        if ($setup->getConnection()->tableColumnExists($salesOrderTable, 'billwerk_order_type')) {
            $setup->getConnection()->dropColumn($salesOrderTable, 'billwerk_order_type');
        }
        if ($setup->getConnection()->tableColumnExists($salesOrderTable, 'billwerk_sub_handle')) {
            $setup->getConnection()->dropColumn($salesOrderTable, 'billwerk_sub_handle');
        }
        if ($setup->getConnection()->tableColumnExists($salesOrderTable, 'billwerk_sub_inv_handle')) {
            $setup->getConnection()->dropColumn($salesOrderTable, 'billwerk_sub_inv_handle');
        }

        // Drop columns from sales_orde_grid table
        $salesOrderGridTable = $setup->getTable('sales_order_grid');
        if ($setup->getConnection()->tableColumnExists($salesOrderGridTable, 'billwerk_order_type')) {
            $setup->getConnection()->dropColumn($salesOrderGridTable, 'billwerk_order_type');
        }
        if ($setup->getConnection()->tableColumnExists($salesOrderGridTable, 'billwerk_sub_handle')) {
            $setup->getConnection()->dropColumn($salesOrderGridTable, 'billwerk_sub_handle');
        }
        if ($setup->getConnection()->tableColumnExists($salesOrderGridTable, 'billwerk_sub_inv_handle')) {
            $setup->getConnection()->dropColumn($salesOrderGridTable, 'billwerk_sub_inv_handle');
        }

        // Drop columns from catalog_product_option_type_value table
        $catalogProductOptionTypeValueTable = $setup->getTable('catalog_product_option_type_value');
        if ($setup->getConnection()->tableColumnExists($catalogProductOptionTypeValueTable, 'billwerk_addon_handle')) {
            $setup->getConnection()->dropColumn($catalogProductOptionTypeValueTable, 'billwerk_addon_handle');
        }

        // Drop columns from salesrule table
        $salesruleTable = $setup->getTable('salesrule');
        if ($setup->getConnection()->tableColumnExists($salesruleTable, 'billwerk_discount_handle')) {
            $setup->getConnection()->dropColumn($salesruleTable, 'billwerk_discount_handle');
        }
        if ($setup->getConnection()->tableColumnExists($salesruleTable, 'billwerk_coupon_code')) {
            $setup->getConnection()->dropColumn($salesruleTable, 'billwerk_coupon_code');
        }

    }

    /**
     * Remove configuration data
     *
     * @return void
     */
    private function removeConfigData(): void
    {
        $configPaths = [
            'payment/billwerkplus_subscription/%', // Use % for wildcard removal
        ];

        foreach ($configPaths as $path) {
            try {
                if (strpos($path, '%') !== false) {
                    // For wildcard paths
                    $this->configResource->getConnection()->delete(
                        $this->configResource->getMainTable(),
                        ['path LIKE ?' => $path]
                    );
                } else {
                    // For exact paths
                    $this->configResource->deleteConfig($path);
                }
            } catch (LocalizedException $e) {
                // Handle exception if needed, e.g., log it
                // For now, we just ignore it
            }
        }
    }

    /**
     * Remove EAV attributes group
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function removeEavAttributesGroup(SchemaSetupInterface $setup): void
    {
        $attributeGroupCodes = [
            'subscriptions-by-billwerk',
            'subscriptions-by-frisbii',
        ];

        // Remove attribute groups
        $tableName = $setup->getTable('eav_attribute_group');
        foreach ($attributeGroupCodes as $attributeGroupCode) {
            $setup->getConnection()->delete($tableName, ['attribute_group_code = ?' => $attributeGroupCode]);
        }

    }
}
