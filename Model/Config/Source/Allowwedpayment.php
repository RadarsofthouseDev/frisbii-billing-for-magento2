<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source;

class Allowwedpayment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return Reepay payment allowwed payments
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'card', 'label' => __('All available debit / credit cards')],
            ['value' => 'dankort', 'label' => __('Dankort')],
            ['value' => 'visa', 'label' => __('VISA')],
            ['value' => 'visa_elec', 'label' => __('VISA Electron')],
            ['value' => 'mc', 'label' => __('MasterCard')],
            ['value' => 'amex', 'label' => __('American Express')],
            ['value' => 'mobilepay_subscriptions', 'label' => __('MobilePay Subscriptions')],
            ['value' => 'vipps_recurring', 'label' => __('Vipps Recurring')],
            ['value' => 'diners', 'label' => __('Diners Club')],
            ['value' => 'maestro', 'label' => __('Maestro')],
            ['value' => 'laser', 'label' => __('Laser')],
            ['value' => 'discover', 'label' => __('Discover')],
            ['value' => 'jcb', 'label' => __('JCB')],
            ['value' => 'china_union_pay', 'label' => __('China Union Pay')],
            ['value' => 'ffk', 'label' => __('Forbrugsforeningen')],
            ['value' => 'applepay', 'label' => __('Apple Pay')],
            ['value' => 'bank_transfer', 'label' => __('Bank Transfer')],
            ['value' => 'cash', 'label' => __('Cash')],
            ['value' => 'other', 'label' => __('Other')],
        ];
    }
}
