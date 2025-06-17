<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Setup\Patch\Data;

use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\CheckoutAgreements\Model\AgreementFactory;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Data as HelperData;

class P001Agreements implements DataPatchInterface
{
    /**
     * @var CollectionFactory
     */
    private $agreementCollectionFactory;

    /**
     * @var AgreementFactory
     */
    private $agreementFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param CollectionFactory $agreementCollectionFactory
     * @param AgreementFactory $agreementFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        CollectionFactory $agreementCollectionFactory,
        AgreementFactory $agreementFactory
    ) {
        $this->setup = $setup;
        $this->agreementCollectionFactory = $agreementCollectionFactory;
        $this->agreementFactory = $agreementFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->setup->startSetup();

        $agreementCollection = $this->agreementCollectionFactory->create();
        $agreementCollection->addFieldToFilter('name', HelperData::TERMS_AND_CONDITIONS_NAME);

        $content = $this->_getAgreementContent();

        if ($agreementCollection->count() === 0) {
            $data = [
                "name" => HelperData::TERMS_AND_CONDITIONS_NAME,
                "is_active" => 1,
                "is_html" => 1,
                "mode" => 1,
                "stores" => [0],
                "checkbox_text" => "Billwerk+ Optimize Terms and Conditions",
                "content" => $content,
                "content_height" => null
            ];

            $agreement = $this->agreementFactory->create();
            $agreement->setData($data);
            $agreement->save();
        }

        $this->setup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Returns the content of the agreement.
     *
     * @return string
     */
    protected function _getAgreementContent()
    {
        return '<h1>Terms of Service</h1>
<h2>Duration</h2>
<p>As long as the membership is not canceled, it will be charged on your payment card at regular intervals. Payment is due in advance. You can terminate your subscription at any time by contacting us via phone or email, but no later than 8 days before the next renewal date.</p>
<h2>Receipt</h2>
<p>After registration and for each charge, a receipt will be sent to your email.</p>
<h2>Payment Card Expiration</h2>
<p>If your payment card expires, you will receive an email with a link to renew your card information.</p>
<h2>Payment Card Information</h2>
<p>By signing up for this subscription, you authorize us to store the necessary payment card information for automatic debit through our payment gateway. This information will be deleted when the subscription expires.</p>
<h2>Price</h2>
<p>All prices include VAT unless otherwise stated.</p>
<h2>Delivery</h2>
<p>Once registration is confirmed, the purchased services can be used, and the product is considered delivered.</p>
<h2>Refunding</h2>
<p>Once a subscription has started, it is not refundable.</p>
<h2>Complaint</h2>
<p>Any form of complaint must be directed to our support team. For questions about your purchase, please contact our support team.</p>
<h2>Contact Permission</h2>
<p>By signing up, you agree that we are allowed to use your email or phone to contact you regarding your subscription and related services. This includes renewal notifications, service updates, and important account information.</p>';
    }
}
