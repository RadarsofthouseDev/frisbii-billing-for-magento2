<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class ErrorHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * @var string
     */
    protected $fileName = '/var/log/billwerk-plus-subscription.log';
}
