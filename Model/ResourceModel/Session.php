<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Session extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('radarsofthouse_billwerkplus_session', 'session_id');
    }
}
