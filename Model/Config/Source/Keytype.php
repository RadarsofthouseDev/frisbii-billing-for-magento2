<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source;

class Keytype implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return Reepay payment key types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Test')],
            ['value' => 1, 'label' => __('Live')],
        ];
    }
}
