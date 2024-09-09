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
use Radarsofthouse\BillwerkPlusSubscription\Helper\Discount;

class DiscountHandle implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Discount
     */
    private $discountHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Data $helper,
        Discount $discountHelper,
        StoreManagerInterface $storeManager
    )
    {
        $this->helper = $helper;
        $this->discountHelper = $discountHelper;
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
        $discounts = $this->discountHelper->list($apiKey);

        if (!empty($discounts)) {
            foreach ($discounts as $discount) {
                $options[] =['label' => "({$discount['handle']}) {$discount['name']}", 'value' => $discount['handle']];
            }
        }
        return $options;
    }
}
