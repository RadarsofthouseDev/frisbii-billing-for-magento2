<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;

class CartProductAddBefore implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * CartProductAddBefore constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param Data $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Data $helper
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
    }

    /**
     * Execute observer method
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $request = $observer->getEvent()->getInfo();

        if ($this->_helper->isBillwerkSubscriptionProduct($product)) {
            if (isset($request['qty']) && $request['qty'] > 1) {
                throw new LocalizedException(__('Subscription products can only be purchased in a quantity of one.'));
            }

            $quote = $this->_checkoutSession->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if ($this->_helper->isBillwerkSubscriptionProduct($item->getProduct())) {
                    throw new LocalizedException(
                        __('Your cart already contains a subscription product. Please complete your purchase or remove the existing subscription product before adding a new one.')
                    );
                }
            }
        }
    }
}
