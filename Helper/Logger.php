<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Radarsofthouse\BillwerkPlusSubscription\Logger\Debug;
use Radarsofthouse\BillwerkPlusSubscription\Logger\Error;
use Radarsofthouse\BillwerkPlusSubscription\Logger\Info;

class Logger extends AbstractHelper
{

    public const CONFIG_PATH = 'log_level';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var mixed
     */
    private $loggerLevel;
    /**
     * @var Debug
     */
    private $debugLogger;
    /**
     * @var Info
     */
    private $infoLogger;
    /**
     * @var Error
     */
    private $errorLogger;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Debug $debug
     * @param Info $info
     * @param Error $error
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Debug $debug,
        Info $info,
        Error $error,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->debugLogger = $debug;
        $this->infoLogger = $info;
        $this->errorLogger = $error;
        $this->loggerLevel = \Monolog\Logger::DEBUG;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Log debug
     *
     * @param string $message
     * @param array $context
     * @param boolean $isApi
     * @return void
     */
    public function addDebug(string $message, array $context = [], bool $isApi = false)
    {
        if ($this->loggerLevel <= \Monolog\Logger::DEBUG) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $logConfig = $this->scopeConfig->getValue('payment/billwerkplus_subscription/log', $storeScope);

            if ($logConfig == 1 && $isApi) {
                $this->debugLogger->debug($message, $context);
            } elseif ($logConfig == 2) {
                $this->debugLogger->debug($message, $context);
            }
        }
    }

    /**
     *  Log info
     *
     * @param string $message
     * @param array $context
     * @param bool $isApi
     * @return void
     */
    public function addInfo(string $message, array $context = [], bool $isApi = false)
    {
        if ($this->loggerLevel <= \Monolog\Logger::INFO) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $logConfig = $this->scopeConfig->getValue('payment/billwerkplus_subscription/log', $storeScope);

            if ($logConfig == 1 && $isApi) {
                $this->infoLogger->info($message, $context);
            } elseif ($logConfig == 2) {
                $this->infoLogger->info($message, $context);
            }
        }
    }

    /**
     * Log error
     *
     * @param string $message
     * @param array $context
     * @param boolean $isApi
     * @return void
     */
    public function addError(string $message, array $context = [], bool $isApi = false)
    {
        if ($this->loggerLevel <= \Monolog\Logger::ERROR) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $logConfig = $this->scopeConfig->getValue('payment/billwerkplus_subscription/log', $storeScope);

            if ($logConfig == 1 && $isApi) {
                $this->errorLogger->error($message, $context);
            } elseif ($logConfig == 2) {
                $this->errorLogger->error($message, $context);
            }
        }
    }
}
