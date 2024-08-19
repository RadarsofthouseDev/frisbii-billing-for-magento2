<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\CheckoutAgreements\Model;

use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory as AgreementCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class AgreementsProviderPlugin
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
     * @var AgreementCollectionFactory
     */
    protected $_agreementCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Constructor
     * 
     * @param CheckoutSession $checkoutSession
     * @param HelperData $helper
     * @param AgreementCollectionFactory $agreementCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        HelperData $helper,
        AgreementCollectionFactory $agreementCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_agreementCollectionFactory = $agreementCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Filter out the Billwerk subscription agreement IDs from the results if the cart doesn't include a Billwerk subscription product.
     *
     * @param AgreementsProvider $subject
     * @param int[] $result
     * @return int[]
     */
    public function afterGetRequiredAgreementIds(AgreementsProvider $subject, $result)
    {
        if (!empty($result)) {
            // Check if the cart include a Billwerk subscription product.
            $hasBillwerkSubscriptionProduct = $this->_hasBillwerkSubscriptionProduct();

            if (!$hasBillwerkSubscriptionProduct) {
                $billwerkSubscriptionAgreementCollection = $this->_agreementCollectionFactory->create();
                $billwerkSubscriptionAgreementCollection->addStoreFilter($this->_storeManager->getStore()->getId());
                $billwerkSubscriptionAgreementCollection->addFieldToFilter('is_active', 1);
                $billwerkSubscriptionAgreementCollection->addFieldToFilter('name', HelperData::TERMS_AND_CONDITIONS_NAME);
                $billwerkSubscriptionAgreementIds = $billwerkSubscriptionAgreementCollection->getAllIds();

                // Filter out the Billwerk subscription agreement IDs from the result
                $result = array_diff($result, $billwerkSubscriptionAgreementIds);
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
