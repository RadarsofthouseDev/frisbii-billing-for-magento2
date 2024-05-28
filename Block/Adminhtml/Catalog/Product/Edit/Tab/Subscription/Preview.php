<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Catalog\Product\Edit\Tab\Subscription;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Plan;

class Preview extends Widget
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Plan
     */
    protected $planHelper;

    /**
     * @var string
     */
    protected $_template = 'product/edit/subscription/preview.phtml';

    /**
     * @param Context $context
     * @param Data $helper
     * @param Plan $planHelper
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Context          $context,
        Data             $helper,
        Plan             $planHelper,
        array            $data = [],
        ?JsonHelper      $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->helper = $helper;
        $this->planHelper = $planHelper;
        parent::__construct($context, $data);
    }

    /**
     * Disable Cache.
     *
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Get plan list.
     *
     * @return array
     */
    public function getPlans()
    {
        $apiKey = $this->helper->getApiKey();
        try {
            $plans = $this->planHelper->getList($apiKey);
        } catch (\Throwable $exception) {
            $plans = [];
        }
        return $plans;
    }

    /**
     * Prepare subscription data.
     *
     * @param  array $plan
     * @return array
     */
    public function prepare($plan)
    {
        $result = [
            'handle' => $plan['handle'],
            'name' => $plan['name'] ?? '',
            'price' => (int)$plan['amount'] / 100,
            'currency' => $plan['currency'],
            'vat' => $plan['vat'] ?? '',
            'setup_fee' => ['enabled' => false,]
        ];

        if (!empty($plan['setup_fee'])) {
            $fee = [
                'enabled' => true,
                'amount' => (int)$plan['setup_fee'] / 100,
                'text' => !empty($plan['setup_fee_text']) ? $plan['setup_fee_text'] : '',
                'handling' => $plan['setup_fee_handling'],
            ];
            $result['setup_fee'] = $fee;
        }

        if (!empty($plan['trial_interval_length'])) {
            $type = 'customize';
            if ($plan['trial_interval_length'] == 7 && $plan['trial_interval_unit'] == 'days') {
                $type = __('7 Days');
            } elseif ($plan['trial_interval_length'] == 14 && $plan['trial_interval_unit'] == 'days') {
                $type = __('14 Days');
            } elseif ($plan['trial_interval_length'] == 1 && $plan['trial_interval_unit'] == 'months') {
                $type = __('1 Month');
            }

            $trial = [
                'type' => $type,
                'length' => $plan['trial_interval_length'],
                'unit' => $plan['trial_interval_unit'],
                'reminder' => false,
            ];

            if (!empty($plan['trial_reminder_email_days'])) {
                $trial['reminder'] = $plan['trial_reminder_email_days'];
            }

            $result['trial'] = $trial;
        }

        if (!empty($plan['schedule_type'])) {
            $type = $plan['schedule_type'];
            if (!empty($plan['schedule_fixed_day']) && !empty($plan['interval_length'])
                && $plan['schedule_type'] == 'month_fixedday') {
                if ($plan['schedule_fixed_day'] == 28) {
                    $type = 'ultimo';
                } elseif ($plan['schedule_fixed_day'] == 1) {
                    if ($plan['interval_length'] == 3) {
                        $type = 'primo';
                    } elseif ($plan['interval_length'] == 6) {
                        $type = 'half_yearly';
                    } elseif ($plan['interval_length'] == 12) {
                        $type = 'month_startdate_12';
                    }
                }
            }
            $result['schedule_type'] = $type;
            $result['schedule_type_txt'] = $this->scheduleTypeText($type);
        }

        if (!empty($plan['interval_length'])) {
            $result['daily'] = $plan['interval_length'];
            $result['month_startdate'] = $plan['interval_length'];

            $typeData = [
                'month' => $plan['interval_length'],
                'day' => !empty($plan['schedule_fixed_day']) ? $plan['schedule_fixed_day'] : '',
                'period' => !empty($plan['partial_period_handling']) ? $plan['partial_period_handling'] : '',
                'proration' => !empty($plan['proration']) ? 'full_day' : 'by_minute',
                'proration_minimum' => !empty($plan['minimum_prorated_amount']) ? $plan['minimum_prorated_amount'] : '',
            ];
            $result['month_fixedday'] = $typeData;

            unset($typeData['day']);
            $result['month_lastday'] = $typeData;

            unset($typeData['month']);
            $result['primo'] = $typeData;
            $result['month_startdate_12'] = $typeData;
            $result['half_yearly'] = $typeData;
            $result['ultimo'] = $typeData;

            $numberToWeekDay = [
                1 => __('Monday'),
                2 => __('Tuesday'),
                3 => __('Wednesday'),
                4 => __('Thursday'),
                5 => __('Friday'),
                6 => __('Saturday'),
                7 => __('Sunday'),
            ];

            $typeData['week'] = $plan['interval_length'];
            $typeData['day'] = !empty($plan['schedule_fixed_day']) ?
                ($numberToWeekDay[$plan['schedule_fixed_day']] ?? '') : '';
            $result['weekly_fixedday'] = $typeData;
        }

        if (!empty($plan['notice_periods'])) {
            $result['notice_period'] = $plan['notice_periods'];
        }

        if (!empty($plan['fixation_periods'])) {
            $plan_meta['contract_periods'] = $plan['fixation_periods'];
        }

        return $result;
    }

    /**
     *  Map schedule type to Text
     *
     * @param string $type
     * @return mixed|string
     */
    public function scheduleTypeText($type)
    {
        $scheduleTypeText = [
            'daily' => __('Day(s)', 'Radarsofthouse_BillwerkPlusSubscription'),
            'month_startdate' => __('Month(s)', 'Radarsofthouse_BillwerkPlusSubscription'),
            'month_fixedday' => __('Fixed day of month', 'Radarsofthouse_BillwerkPlusSubscription'),
            'month_lastday' => __('Last day of month', 'Radarsofthouse_BillwerkPlusSubscription'),
            'primo' => __('Quarterly Primo', 'Radarsofthouse_BillwerkPlusSubscription'),
            'ultimo' => __('Quarterly Ultimo', 'Radarsofthouse_BillwerkPlusSubscription'),
            'half_yearly' => __('Half-yearly', 'Radarsofthouse_BillwerkPlusSubscription'),
            'month_startdate_12' => __('Yearly', 'Radarsofthouse_BillwerkPlusSubscription'),
            'weekly_fixedday' => __('Fixed day of week', 'Radarsofthouse_BillwerkPlusSubscription'),
            'manual' => __('Manual', 'Radarsofthouse_BillwerkPlusSubscription'),
        ];
        return $scheduleTypeText[$type] ?? '';
    }
}
