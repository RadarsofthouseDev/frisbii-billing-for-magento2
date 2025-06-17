<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\CheckoutAgreements\Model\AgreementsConfigProvider as BaseConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;
use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\Store\Model\ScopeInterface;

class AgreementsConfigProvider extends BaseConfigProvider
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfiguration;

    /**
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface
     */
    protected $checkoutAgreementsRepository;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface
     */
    private $checkoutAgreementsList;

    /**
     * @var \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter
     */
    private $activeStoreAgreementsFilter;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface $checkoutAgreementsRepository
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface|null $checkoutAgreementsList
     * @param \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter|null $activeStoreAgreementsFilter
     * @param CheckoutSession $checkoutSession
     * @param HelperData $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface $checkoutAgreementsRepository,
        \Magento\Framework\Escaper $escaper,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList = null,
        \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter $activeStoreAgreementsFilter = null,
        CheckoutSession $checkoutSession,
        HelperData $helper
    ) {
        parent::__construct(
            $scopeConfiguration,
            $checkoutAgreementsRepository,
            $escaper,
            $checkoutAgreementsList,
            $activeStoreAgreementsFilter
        );
        $this->scopeConfiguration = $scopeConfiguration;
        $this->checkoutAgreementsRepository = $checkoutAgreementsRepository;
        $this->escaper = $escaper;
        $this->checkoutAgreementsList = $checkoutAgreementsList;
        $this->activeStoreAgreementsFilter = $activeStoreAgreementsFilter;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
    }

    /**
     * @inheritdoc
     */
    protected function getAgreementsConfig()
    {
        $agreementConfiguration = [];
        $isAgreementsEnabled = $this->scopeConfiguration->isSetFlag(
            AgreementsProvider::PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );

        $agreementsList = $this->checkoutAgreementsList->getList(
            $this->activeStoreAgreementsFilter->buildSearchCriteria()
        );
        $agreementConfiguration['isEnabled'] = (bool)($isAgreementsEnabled && count($agreementsList) > 0);

        // Check if the cart has Billwerk subscription products
        $hasBillwerkSubscriptionProduct = $this->_hasBillwerkSubscriptionProduct();

        foreach ($agreementsList as $agreement) {

            if (!$hasBillwerkSubscriptionProduct && $agreement->getName() == HelperData::TERMS_AND_CONDITIONS_NAME) {
                // Skip adding the specific term and condition if no subscription product is found
                continue;
            }

            $agreementConfiguration['agreements'][] = [
                'content' => $agreement->getIsHtml()
                    ? $agreement->getContent()
                    : nl2br($this->escaper->escapeHtml($agreement->getContent())),
                'checkboxText' => $this->escaper->escapeHtml($agreement->getCheckboxText()),
                'mode' => $agreement->getMode(),
                'agreementId' => $agreement->getAgreementId(),
                'contentHeight' => $agreement->getContentHeight()
            ];
        }

        return $agreementConfiguration;
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
