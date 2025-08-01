<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class P000ProductAttribute implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory          $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        // Add or update the 'billwerk_sub_plan' and 'billwerk_sub_enabled' attributes for products
        if ($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'billwerk_sub_plan')) {
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'billwerk_sub_plan',
                [
                    'type' => 'varchar',
                    'label' => 'Subscription Plan',
                    'input' => 'select',
                    'source' => \Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\SubscriptionPlan::class,
                    'sort_order' => 30,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Subscriptions by Frisbii',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'used_for_promo_rules' => true,
                    'required' => false,
                    'apply_to' => 'simple,virtual',
                ]
            );
        } else {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'billwerk_sub_plan',
                [
                    'type' => 'varchar',
                    'label' => 'Subscription Plan',
                    'input' => 'select',
                    'source' => \Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\SubscriptionPlan::class,
                    'sort_order' => 30,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Subscriptions by Frisbii',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'used_for_promo_rules' => true,
                    'required' => false,
                    'apply_to' => 'simple,virtual',
                ]
            );
        }

        // Add or update the 'billwerk_sub_enabled' attribute for products
        if ($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'billwerk_sub_enabled')) {
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'billwerk_sub_enabled',
                [
                    'type' => 'int',
                    'label' => 'Enable Subscription',
                    'input' => 'boolean',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 29,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'default' => 1,
                    'group' => 'Subscriptions by Frisbii',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'used_for_promo_rules' => true,
                    'required' => false,
                    'apply_to' => 'simple,virtual',
                ]
            );
        } else {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'billwerk_sub_enabled',
                [
                    'type' => 'int',
                    'label' => 'Enable Subscription',
                    'input' => 'boolean',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 29,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'default' => 1,
                    'group' => 'Subscriptions by Frisbii',
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'used_for_promo_rules' => true,
                    'required' => false,
                    'apply_to' => 'simple,virtual',
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Rollback all changes, done by this patch
     *
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'billwerk_sub_plan');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'billwerk_sub_enabled');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
