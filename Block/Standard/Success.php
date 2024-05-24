<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block\Standard;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class Success extends Template
{
    /**
     * Get Continue URL
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
