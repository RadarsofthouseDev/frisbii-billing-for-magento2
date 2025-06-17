<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class P002UpdateGroupLabel implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;
    private AttributeSetCollectionFactory $attributeSetCollectionFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        AttributeSetCollectionFactory $attributeSetCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Get attribute group ID for "Subscriptions by Billwerk+"
        $attributeSetCollection = $this->attributeSetCollectionFactory->create();
        $attributeSetCollection->addFieldToSelect('*');

        // Update attribute group name from "Subscriptions by Billwerk+" to "Subscriptions by Frisbii"
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable('eav_attribute_group'),
            ['attribute_group_name' => 'Subscriptions by Frisbii'],
            ['attribute_group_name = ?' => 'Subscriptions by Billwerk+']
        );

        // Update agreement name in checkout_agreement table
        $connection->update(
            $this->moduleDataSetup->getTable('checkout_agreement'),
            ['name' => 'frisbii_billing_terms_and_conditions'],
            ['name = ?' => 'billwerk_plus_optimize_terms_and_conditions']
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [
            \Radarsofthouse\BillwerkPlusSubscription\Setup\Patch\Data\P001Agreements::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
