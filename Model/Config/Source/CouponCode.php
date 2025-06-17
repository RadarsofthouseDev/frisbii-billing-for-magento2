<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\Config\Source;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Coupon;

class CouponCode implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Coupon
     */
    private $couponHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CouponCode constructor.
     *
     * @param Data $helper
     * @param Coupon $couponHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        Coupon $couponHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->couponHelper = $couponHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Get Current Store ID
     *
     * @return null|int
     */
    private function getCurrenStoreId()
    {
        $storeId = null;
        try {
            $currentStore = $this->storeManager->getStore();
            $storeId = $currentStore->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $storeId;
    }

    /**
     * Return Reepay payment icons
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['label' => ' ', 'value' => ''],];
        $storeId = $this->getCurrenStoreId();
        try {
            $apiKey = $this->helper->getApiKey($storeId);
        } catch (NoSuchEntityException $e) {
            return $options;
        }
        $coupons = $this->couponHelper->list($apiKey);

        if (!empty($coupons)) {
            foreach ($coupons as $coupon) {
                $options[] =['label' => "({$coupon['code']}) {$coupon['name']}", 'value' => $coupon['code']];
            }
        }
        return $options;
    }
}
