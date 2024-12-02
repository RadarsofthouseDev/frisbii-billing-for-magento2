<?php
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\SalesRule\Model;

use Closure;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Radarsofthouse\BillwerkPlusSubscription\Helper\Logger;

class RulesApplier
{

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Registry $registry
     * @param Logger $logger
     */
    public function __construct(
        Registry $registry,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     *  Remove all sale rule and coupon.
     *
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param Closure $proceed
     * @param AbstractItem $item
     * @param array $rules
     * @param bool $skipValidation
     * @param  mixed $couponCode
     * @return array
     */
    public function aroundApplyRules(
        \Magento\SalesRule\Model\RulesApplier $subject,
        Closure $proceed,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    ) {

        if ($this->registry->registry('billwerk_subscription_webhook_renewal_order')) {
            $this->logger->addInfo(__METHOD__ . 'Remove all sale rule and coupon. ', ['itemId' => $item->getId(), 'couponCode' => $couponCode]);
            $rules = [];
            $couponCode = '';
        }
        $result = $proceed($item, $rules, $skipValidation, $couponCode);
        return $result;
    }
}
