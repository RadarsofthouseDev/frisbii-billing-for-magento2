<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;

class Addon extends AbstractModifier
{

    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\Addon
     */
    protected $addon;

    public function __construct(
        \Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\Addon $addon
    ) {
        $this->addon = $addon;
    }

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->addFields();

        return $this->meta;
    }

    /**
     * Adds fields to the meta-data
     */
    protected function addFields()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;

        // Add fields to the values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children'][$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children'][$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getAddonFieldsConfig()
        );
    }

    /**
     * The custom option fields config
     *
     * @return array
     */
    protected function getAddonFieldsConfig()
    {
        $fields['billwerk_addon_handle'] = $this->getSelectField();

        return $fields;
    }

    /**
     * Get description field config
     *
     * @return array
     */
    protected function getSelectField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Frisbii Addon'),
                        'componentType' => Field::NAME,
                        'formElement' => Select::NAME,
                        'dataScope' => 'billwerk_addon_handle',
                        'dataType' => Text::NAME,
                        'sortOrder' => 42,
                        'options' => $this->addon->getAllOptions(),
                    ],
                ],
            ],
        ];
    }
}
