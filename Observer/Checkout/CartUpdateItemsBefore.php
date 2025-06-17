<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data;

class CartUpdateItemsBefore implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * CartUpdateItemsBefore constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
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
        $cart = $observer->getEvent()->getCart();
        $info = $observer->getEvent()->getInfo()->getData();

        foreach ($cart->getQuote()->getAllItems() as $item) {
            if ($this->_helper->isBillwerkSubscriptionProduct($item->getProduct())) {
                $itemId = $item->getId();
                if (isset($info[$itemId]['qty']) && $info[$itemId]['qty'] > 1) {
                    throw new LocalizedException(
                        __('Subscription products can only be purchased in a quantity of one.')
                    );
                }
            }
        }
    }
}
