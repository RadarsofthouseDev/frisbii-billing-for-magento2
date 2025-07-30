<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\MultiSelect;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Catalog\Product\Edit\Tab\Subscription\Preview;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Plan;

class Subscription extends AbstractModifier
{
    public const ENABLED_PRODUCT_TYPES = ['simple', 'virtual'];

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var array
     */
    protected $meta = [];
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Plan
     */
    protected $planHelper;
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param Data $helper
     * @param Plan $planHelper
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        Data $helper,
        Plan $planHelper,
        LayoutFactory $layoutFactory
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        $this->planHelper = $planHelper;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $product = $this->locator->getProduct();
        if ($product && !empty($product->getTypeId())
            && in_array($product->getTypeId(), static::ENABLED_PRODUCT_TYPES)) {
            $this->meta = array_replace_recursive(
                $meta,
                [
                    'subscriptions-by-frisbii' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'visible' => true,
                                    'sortOrder' => 11,
                                ]
                            ]
                        ],
                        'children' => [
                            'billwerk_sub_enabled' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'visible' => true,
                                        ]
                                    ]
                                ]
                            ],
                            'billwerk_sub_plan' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'visible' => true,
                                        ]
                                    ]
                                ]
                            ],
                            "billwerk_sub_view_container" => [
                                "arguments" => [
                                    "data" => [
                                        "config" => [
                                            "formElement" => "container",
                                            "componentType" => "container",
                                            'component' => 'Magento_Ui/js/form/components/html',
                                            "required" => 0,
                                            "sortOrder" => 15,
                                            "content" => $this->layoutFactory->create()
                                                ->createBlock(Preview::class)->toHtml(),
                                            "additionalClasses" => "admin__field"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ]
            );
        }
        return $this->meta;
    }
}
