<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source;

class SubStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Return Reepay payment icons
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => ' '],
            ['value' => '0', 'label' => __('Inactive')],
            ['value' => '1', 'label' => __('Active')],
        ];
    }
}
