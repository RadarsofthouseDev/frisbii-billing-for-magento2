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
use Radarsofthouse\BillwerkPlusSubscription\Helper\Addon as AddonHelper;

class Addon extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var AddonHelper
     */
    private $addonHelper;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $helper
     * @param AddonHelper $addonHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        AddonHelper $addonHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->addonHelper = $addonHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Get Current Store ID
     *
     * @return int|void
     */
    private function getCurrenStoreId()
    {
        $storeId = null;
        try {
            $currentStore = $this->storeManager->getStore();
            $storeId = $currentStore->getId();
        } catch (NoSuchEntityException $e) {
            return;
        }
        return $storeId;
    }

    /**
     * Return Reepay payment key types
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        $options = [['label' => ' ', 'value' => ''],];
        $storeId = $this->getCurrenStoreId();
        $apiKey = $this->helper->getApiKey($storeId);
        $plans = $this->addonHelper->getList($apiKey);

        if (!empty($plans)) {
            foreach ($plans as $plan) {
                $options[] = ['label' => $plan['name'], 'value' => $plan['handle']];
            }
        }
        return $options;
    }

    /**
     * Get All Options
     *
     * @return array|\string[][]|null
     * @throws NoSuchEntityException
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = $this->toOptionArray();
        }
        return $this->_options;
    }
}
