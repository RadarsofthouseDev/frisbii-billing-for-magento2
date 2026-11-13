<?php

/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerHandle extends Column
{
    private Escaper $escaper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        //        dump($dataSource);
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                // Assuming 'custom_column' is the field name in the custom table
                $customColumnValue = (string)$item['customer_handle'];

                // You can modify the rendering logic as needed
                // For example, you can add HTML markup or format the value
                if (!empty($customColumnValue)) {
                    $url = "https://app.frisbii.com/#/rp/customers/customers/customer/" . rawurlencode($customColumnValue);
                    $customColumnValue = sprintf(
                        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                        $this->escaper->escapeHtmlAttr($url),
                        $this->escaper->escapeHtml($customColumnValue)
                    );
                }
                $item[$this->getData('name')] = $customColumnValue;
            }
        }
        //        dump($dataSource);
        return $dataSource;
    }
}
