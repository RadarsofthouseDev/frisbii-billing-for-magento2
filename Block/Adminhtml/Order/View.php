<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Adminhtml\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterfaceFactory;

class View extends \Magento\Backend\Block\Template
{

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerSubscriberInterfaceFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry             $registry,
        ProductRepositoryInterface              $productRepository,
        CustomerSubscriberInterfaceFactory      $customerFactory,
        array                                   $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->productRepository = $productRepository;
        $this->customerFactory = $customerFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get the current order object
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Check if subscription to show.
     *
     * @return bool
     */
    public function isShow()
    {
        if ($this->getSubscriptionHandle()) {
            return true;
        }
        return false;
    }

    /**
     * Get customer handle.
     *
     * @return mixed
     */
    public function getCustomerHandle()
    {
        $customerId = $this->getOrder()->getCustomerId();
        $customer = $this->customerFactory->create()->load($customerId, 'customer_id');
        return $customer->getCustomerHandle();
    }

    /**
     * Get subscription handle.
     *
     * @return mixed
     */
    public function getSubscriptionHandle()
    {
        return $this->getOrder()->getBillwerkSubHandle();
    }

    /**
     * Get plan handle from order item.
     *
     * @return mixed|string
     */
    public function getPlanHandle()
    {
        $plan = '';
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($this->getOrder()->getAllVisibleItems() as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                if ($subEnabled && !empty($subPlan)) {
                    $plan = $subPlan;
                }
            } catch (NoSuchEntityException $exception) {
                continue;
            }
        }
        return $plan;
    }
}
