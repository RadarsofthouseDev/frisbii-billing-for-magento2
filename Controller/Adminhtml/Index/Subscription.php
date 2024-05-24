<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Layout;

class Subscription extends \Magento\Customer\Controller\Adminhtml\Index
{

    /**
     * Execute.
     *
     * @return ResultInterface|Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        return $this->resultLayoutFactory->create();
    }
}
