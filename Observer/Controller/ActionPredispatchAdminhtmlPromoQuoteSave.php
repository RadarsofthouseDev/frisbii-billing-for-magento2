<?php
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Controller;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class ActionPredispatchAdminhtmlPromoQuoteSave implements ObserverInterface
{

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
//        dd($observer->getEvent()->getData());
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getEvent()->getRequest();

        $data = $request->getPostValue();
        if(!empty($data['billwerk_coupon_code'])) {
            $data['coupon_type'] = '2';
            $data['coupon_code'] = $data['billwerk_coupon_code'];
            $observer->getEvent()->getRequest()->setPostValue($data);
        }
    }
}
