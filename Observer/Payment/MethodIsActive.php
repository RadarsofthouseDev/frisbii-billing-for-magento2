<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Payment;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class MethodIsActive implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CheckoutSession            $checkoutSession,
        Session                    $customerSession,
        ProductRepositoryInterface $productRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($observer->getEvent()->getMethodInstance()->getCode() == "billwerkplus_subscription") {
            $checkResult = $observer->getEvent()->getResult();
            try {
                $quote = $this->checkoutSession->getQuote();
                if (!$quote) {
                    $observer->getEvent()->getQuote();
                }
                if ($quote && $quote->getItems()) {
                    foreach ($quote->getItems() as $item) {
                        $product = $this->productRepository->get($item->getSku());
                        $subEnabledAttribute = $product->getCustomAttribute('billwerk_sub_enabled');
                        $subEnabled = null !== $subEnabledAttribute ? $subEnabledAttribute->getValue() : 0;
                        $subPlanAttribute = $product->getCustomAttribute('billwerk_sub_plan');
                        $subPlan = null !== $subPlanAttribute ? $subPlanAttribute->getValue() : '';
                        if ($subEnabled && !empty($subPlan)) {
                            $checkResult->setData('is_available', true);
                            return;
                        }
                    }
                }
            } catch (NoSuchEntityException|LocalizedException $e) {
                return;
            }
            $checkResult->setData('is_available', false);
        }
    }
}
