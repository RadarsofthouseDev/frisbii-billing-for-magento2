<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\CheckoutAgreements\Block;

use Magento\CheckoutAgreements\Block\Agreements;
use Magento\Checkout\Model\Session as CheckoutSession;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;

class AgreementsPlugin
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * Constructor
     * 
     * @param CheckoutSession $checkoutSession
     * @param HelperData $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        HelperData $helper
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
    }

    /**
     * After plugin for getAgreements method
     * 
     * @param Agreements $subject
     * @param \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection $result
     * @return \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection
     */
    public function afterGetAgreements(Agreements $subject, $result)
    {
        // Check if the cart has Billwerk subscription products
        $hasBillwerkSubscriptionProduct = $this->_hasBillwerkSubscriptionProduct();

        // Remove the specific term and condition if no subscription product is found
        if (!$hasBillwerkSubscriptionProduct) {
            foreach ($result as $key => $agreement) {
                if ($agreement->getName() == HelperData::TERMS_AND_CONDITIONS_NAME) {
                    $result->removeItemByKey($key);
                }
            }
        }

        return $result;
    }

    /**
     * Check if the cart has a subscription product
     * 
     * @return bool
     */
    protected function _hasBillwerkSubscriptionProduct()
    {
        $quote = $this->_checkoutSession->getQuote();
        foreach ($quote->getAllItems() as $item) {
            $product = $item->getProduct();
            if ($this->_helper->isBillwerkSubscriptionProduct($product)) {
                return true;
            }
        }
        return false;
    }
}
