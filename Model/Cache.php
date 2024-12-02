<?php
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

class Cache extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{

    const TYPE_IDENTIFIER = 'billwerk_plus_optimize_cache';
    const CACHE_TAG = 'BILLWERK_PLUS_OPTIMIZE';

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     */
    public function __construct(
        \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
