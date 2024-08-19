<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;

class MethodIsActive implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @param CheckoutSession $checkoutSession
     * @param HelperData $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        HelperData $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
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
        $checkResult = $observer->getEvent()->getResult();

        // Allow only "billwerkplus_subscription" payment method if the cart included a Billwerk Subscription Product
        if ($this->_hasBillwerkSubscriptionProduct()) {
            if ($observer->getEvent()->getMethodInstance()->getCode() == "billwerkplus_subscription") {
                $checkResult->setData('is_available', true);
            } else {
                $checkResult->setData('is_available', false);
            }
        }
    }

    /**
     * Check if the cart has a subscription product
     * 
     * @return bool
     */
    protected function _hasBillwerkSubscriptionProduct()
    {
        $quote = $this->checkoutSession->getQuote();
        foreach ($quote->getAllItems() as $item) {
            $product = $item->getProduct();
            if ($this->helper->isBillwerkSubscriptionProduct($product)) {
                return true;
            }
        }
        return false;
    }
}
