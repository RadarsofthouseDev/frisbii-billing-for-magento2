<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source;

class Log implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return Reepay payment display types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Disabled')],
            ['value' => 1, 'label' => __('Only Billwerk+ API')],
            ['value' => 2, 'label' => __('Debug mode')],
        ];
    }
}
