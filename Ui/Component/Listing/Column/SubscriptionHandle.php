<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class SubscriptionHandle extends Column
{
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
                $customColumnValue = $item['billwerk_sub_handle'];

                // You can modify the rendering logic as needed
                // For example, you can add HTML markup or format the value
                if (!empty($customColumnValue)) {
                    $url = "https://admin.billwerk.plus/#/rp/subscriptions/subscription/".$customColumnValue;
                    $customColumnValue = '<a href="'. $url .'" target="_blank">'. $customColumnValue .'</a>';
                }
                $item[$this->getData('name')] = $customColumnValue;
            }
        }
//        dump($dataSource);
        return $dataSource;
    }
}
