<?php
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\SalesRule\Model\Rule\Metadata;

use Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\CouponCode;
use Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source\DiscountHandle;

class ValueProvider
{

    /**
     * @var DiscountHandle
     */
    private $discountHandle;

    /**
     * @var CouponCode
     */
    private $couponCode;

    /**
     * Constructor.
     *
     * @param DiscountHandle $discountHandle
     * @param CouponCode $couponCode
     */
    public function __construct(
        DiscountHandle $discountHandle,
        CouponCode $couponCode
    ) {
        $this->discountHandle = $discountHandle;
        $this->couponCode = $couponCode;
    }

    /**
     * After getMetadataValues plugin to add custom fields for Billwerk+.
     *
     * @param \Magento\SalesRule\Model\Rule\Metadata\ValueProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetMetadataValues(
        \Magento\SalesRule\Model\Rule\Metadata\ValueProvider $subject,
        $result
    ) {

        $result['rule_information']['children']['billwerk_coupon_code'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'select',
                        'formElement' => 'select',
                        'componentType' => 'field',
                        'label' => __('Billwerk+ Coupon code'),
                        'dataScope' => 'billwerk_coupon_code',
                        'sortOrder' => 49,
                        'options' => $this->couponCode->toOptionArray(),
                        'notice' => _('Billwerk+ Coupon code will replace Magento Coupon code.')
                    ],
                ],
            ],
        ];

        $result['rule_information']['children']['billwerk_discount_handle'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'select',
                        'formElement' => 'select',
                        'componentType' => 'field',
                        'label' => __('Billwerk+ Discount handle'),
                        'dataScope' => 'billwerk_discount_handle',
                        'sortOrder' => 50,
                        'options' => $this->discountHandle->toOptionArray(),
                        'notice' => _('Discount handle when "No Coupon".')
                    ],
                ],
            ],
        ];
        return $result;
    }
}
