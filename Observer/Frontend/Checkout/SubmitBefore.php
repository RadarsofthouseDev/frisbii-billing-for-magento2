<?php
/**
 * Copyright © BillwerkPlusSubscription All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Frontend\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;

class SubmitBefore implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Magento\Customer\Api\Data\CustomerInterfaceFactory   */
    protected $customerInterfaceFactory;

    /** @var \Magento\Framework\Reflection\DataObjectProcessor  */
    protected $dataProcessor;

    /** @var \Magento\Customer\Model\Session  */
    protected $customerSession;

    /** @var \Magento\Customer\Model\AccountManagement */
    protected $accountManagement;

    /** @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Data */
    protected $helper;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Framework\Encryption\EncryptorInterface */
    protected $encryptor;

    /** @var \Magento\Framework\Mail\Template\TransportBuilder  */
    protected $transportBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $storeManager;

    /** @var \Magento\Framework\Translate\Inline\StateInterface  */
    protected $inlineTranslation;

    /** @var \Magento\Framework\Math\Random  */
    protected $_mathRandom;

    /** @var \Magento\Framework\UrlInterface  */
    protected $urlBuilder;

    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger
     */
    protected $logger;

    /**
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerInterfaceFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper
     * @param \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger $logger
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerInterfaceFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper,
        \Radarsofthouse\BillwerkPlusSubscription\Helper\Logger $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerInterfaceFactory  = $customerInterfaceFactory;
        $this->dataProcessor = $dataProcessor;
        $this->customerSession = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->messageManager = $messageManager;
        $this->encryptor = $encryptor;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_mathRandom = $mathRandom;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Before Submit checkout
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getOrder();
        $storeId = $order->getStoreId();

        // If Payment method are not Billwerk+
        if (!$this->helper->isOurPaymentMethod($quote->getPayment()->getMethod())) {
            return;
        }

        // Skip process below if "allow_to_create_customer" = NO
        if (!$this->helper->getConfig('allow_to_create_customer', $storeId)) {
            $this->logger->addInfo(__METHOD__);
            $this->logger->addInfo("Checkout by guest and auto create customer function is disabled. Email: {$quote->getCustomerEmail()}");
            $this->messageManager->addErrorMessage(__("There is subscription product in your cart. Please login before placing the order."));
            throw new LocalizedException(
                __('Please login or create before placing the order.')
            );
        }

        $customerId = $quote->getCustomerId();
        // If $customerId is blank = Not login.
        if ($quote->getCheckoutMethod() === CartManagementInterface::METHOD_GUEST || !$customerId) {
            // Get customer data by email in the quote.
            $customer = $this->getCustomerByEmail($quote->getCustomerEmail());

            // If the email already exist in the customer list.
            if ($customer) {
                $this->messageManager->addErrorMessage(__("There is subscription product in your cart and you
                already have a customer account so please login before placing the order."));
                throw new LocalizedException(
                    __('Please login before placing the order.')
                );
            } else {
                $customer = $this->createCustomer($quote);
                if ($customer === null) {
                    $this->messageManager->addErrorMessage(
                        __("Can't create new customer by email %1", $quote->getCustomerEmail())
                    );
                    throw new LocalizedException(
                        __('Cannot create new customer.')
                    );
                }

                $order->setCustomerId($customer->getId());
                $order->setCustomerIsGuest(0);
                $order->setCustomerGroupId($customer->getGroupId());
            }
        }
    }

    /**
     * Get Customer by email
     *
     * @param string $email
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerByEmail($email)
    {
        try {
            // Retrieve customer by email
            return $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            // Handle the case where the customer does not exist
            return null;
        } catch (\Exception $e) {
            // Handle other potential exceptions
            throw $e;
        }
    }

    /**
     * Create customer
     *
     * @param object $quote
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function createCustomer($quote)
    {
        // Create a new customer
        // Get Website ID
        $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $storeName = $this->storeManager->getStore($storeId)->getName();
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->customerInterfaceFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->setStoreId($storeId);
        $customer->setCreatedIn($storeName);
        $customer->setEmail($quote->getCustomerEmail());
        if ($quote->getCustomerFirstname() !== null && $quote->getCustomerLastname() !== null
        ) {
            $customer->setFirstname((string)$quote->getCustomerFirstname());
            $customer->setLastname((string)$quote->getCustomerLastname());
            if ($quote->getCustomerMiddlename() !== null) {
                $customer->setMiddlename((string)$quote->getCustomerMiddlename());
            }
        } elseif ($quote->getBillingAddress()) {
            $customer->setFirstname((string)$quote->getBillingAddress()->getFirstname());
            $customer->setLastname((string)$quote->getBillingAddress()->getLastname());
            if ($quote->getBillingAddress()->getMiddlename() !== null) {
                $customer->setMiddlename((string)$quote->getBillingAddress()->getMiddlename());
            }
        }

        // Generate a random password and save customer
        $password = $this->generatePassword();
        $this->customerRepository->save($customer, $password);

        // Retrieve customer by email to ensure they were saved correctly
        $savedCustomer = $this->getCustomerByEmail($quote->getCustomerEmail());
        if ($savedCustomer) {
            try {
                $this->sendCustomEmail($savedCustomer);
            }catch (LocalizedException $e) {
                $this->logger->addInfo(__METHOD__);
                $this->logger->addInfo("Can't send email to " . $savedCustomer->getEmail() . " with Customer Id: " . $savedCustomer->getId());
            }
            return $savedCustomer;
        } else {
            return null;
        }
    }

    /**
     * Generate password
     *
     * @param int $length
     * @return string
     */
    protected function generatePassword($length = 8)
    {
        return $this->encryptor->getHash(uniqid(), true);
    }

    /**
     * Generate token
     *
     * @param object $customer
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function getToken($customer)
    {
        $customer = $this->customerRepository->get($customer->getEmail(), $customer->getWebsiteId());
        $newPasswordToken = $this->_mathRandom->getUniqueHash();
        $this->accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);
        return $newPasswordToken;
    }

    /**
     * Generate reset password link
     *
     * @param object $customer
     * @return string
     * @throws LocalizedException
     */
    public function generatePasswordResetLink($customer)
    {
        try {
            $token = $this->getToken($customer);
            $resetUrl = $this->urlBuilder->getUrl(
                'customer/account/createPassword',
                ['id' => $customer->getId(), 'token' => $token]
            );
            return $resetUrl;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Get Store ID
     *
     * @param int $websiteId
     * @return int
     * @throws LocalizedException
     */
    public function getStoreId($websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    /**
     * Send Custom email
     *
     * @param object $customer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\MailException
     */
    protected function sendCustomEmail($customer)
    {
        $this->inlineTranslation->suspend();

        $storeId = $this->getStoreId($customer->getWebsiteId());

        $templateParams = [];
        $templateParams['id'] = $customer->getId();
        $templateParams['email'] = $customer->getEmail();
        $templateParams['name'] = $customer->getFirstname() . " " . $customer->getLastname();
        $templateParams['create_pass_link'] = $this->generatePasswordResetLink($customer);
        $templateParams['store'] = $this->storeManager->getStore($storeId);

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($templateParams);

        $customerName = $customer->getFirstname() . " " . $customer->getLastname();

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('billwerk_subscription_new_customer') // Template ID
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars(['customer' => $postObject])
                ->setFrom('general')
                ->addTo($customer->getEmail(), $customerName)
                ->getTransport();
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
