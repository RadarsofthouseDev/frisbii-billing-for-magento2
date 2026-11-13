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
    public const REDACTED_VALUE = '[REDACTED]';

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
            $message = $this->sanitizeMessage($message);
            $context = $this->sanitizeContext($context, (int)$logConfig === 2);

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
            $message = $this->sanitizeMessage($message);
            $context = $this->sanitizeContext($context, (int)$logConfig === 2);

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
            $message = $this->sanitizeMessage($message);
            $context = $this->sanitizeContext($context, (int)$logConfig === 2);

            if ($logConfig == 1 && $isApi) {
                $this->errorLogger->error($message, $context);
            } elseif ($logConfig == 2) {
                $this->errorLogger->error($message, $context);
            }
        }
    }

    /**
     * Remove or sanitize sensitive values before they reach Monolog.
     *
     * @param mixed $context
     * @param bool $debugMode
     * @param string $parentKey
     * @return array
     */
    private function sanitizeContext($context, $debugMode = false, $parentKey = '')
    {
        if (!is_array($context)) {
            return [];
        }

        $sanitizedContext = [];
        foreach ($context as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);

            if ($this->isOmittedKey($normalizedKey)) {
                if ($debugMode && $this->isStructuredDebugKey($normalizedKey) && is_array($value)) {
                    $sanitizedContext[$key] = $this->sanitizeContext($value, true, $normalizedKey);
                } elseif ($debugMode && $normalizedKey === 'exception_error' && is_scalar($value)) {
                    $sanitizedContext[$key] = $this->sanitizeMessage($value);
                }
                continue;
            }

            if ($this->isSessionReferenceKey($normalizedKey, $parentKey) && is_scalar($value)) {
                $sanitizedContext[$key] = $this->maskSessionField($normalizedKey, $value);
                continue;
            }

            if ($this->isCardReferenceKey($normalizedKey, $parentKey) && is_scalar($value)) {
                $sanitizedContext[$key] = $this->maskCardReference($normalizedKey, $value);
                continue;
            }

            if ($this->isSafeKey($normalizedKey) && (is_scalar($value) || $value === null)) {
                $sanitizedContext[$key] = $this->maskKnownIdentifier($value);
                continue;
            }

            if ($this->isEmailKey($normalizedKey) && is_scalar($value)) {
                $sanitizedContext[$key] = $this->maskEmail($value);
                continue;
            }

            if ($this->isMessageKey($normalizedKey) && is_scalar($value)) {
                $sanitizedContext[$key] = $this->sanitizeMessage($value);
                continue;
            }

            if ($debugMode && $this->isDebugContainerKey($normalizedKey) && is_array($value)) {
                $sanitizedContext[$key] = $this->sanitizeContext($value, true, $normalizedKey);
                continue;
            }

            if ($this->isSafeCustomerHandle($normalizedKey, $value)) {
                $sanitizedContext[$key] = $value;
                continue;
            }

            // Plan name is merchant-defined, not personal data (customers have first_name/last_name).
            if ($normalizedKey === 'name' && is_scalar($value)
                && (isset($context['interval_length']) || isset($context['schedule_type']))
            ) {
                $sanitizedContext[$key] = $value;
                continue;
            }

            // Payment method "reference" may hold the ca_ handle: keep a short prefix for tracing.
            if ($normalizedKey === 'reference' && is_scalar($value)
                && $this->maskKnownIdentifier($value) !== $value
            ) {
                $sanitizedContext[$key] = $this->maskKnownIdentifier($value);
                continue;
            }

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitizedContext[$key] = self::REDACTED_VALUE;
                continue;
            }

            if (is_array($value)) {
                $sanitizedContext[$key] = $this->sanitizeContext($value, $debugMode, $normalizedKey);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $sanitizedContext[$key] = $this->isEmailValue($value)
                    ? $this->maskEmail($value)
                    : $this->maskKnownIdentifier($value);
                continue;
            }

            $sanitizedContext[$key] = self::REDACTED_VALUE;
        }

        return $sanitizedContext;
    }

    /**
     * Normalize context keys for case and naming variations.
     *
     * @param mixed $key
     * @return string
     */
    private function normalizeKey($key)
    {
        $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', (string)$key);

        return strtolower(str_replace(['-', ' '], '_', $key));
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isOmittedKey($key)
    {
        return in_array(
            $key,
            [
                'body',
                'payload',
                'request',
                'response',
                'trace',
                'exception',
                'exception_error',
                'error',
                'errors',
                'response_errors',
            ],
            true
        );
    }

    /**
     * Keep structured API data in debug mode after recursive sanitization.
     *
     * @param string $key
     * @return bool
     */
    private function isStructuredDebugKey($key)
    {
        return in_array($key, ['payload', 'request', 'response', 'errors', 'response_errors'], true);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isDebugContainerKey($key)
    {
        return in_array(
            $key,
            ['charge', 'customer', 'create_customer', 'order', 'order_lines', 'session'],
            true
        );
    }

    /**
     * Keep identifiers that help correlate a request without exposing personal data.
     *
     * @param string $key
     * @return bool
     */
    private function isSafeKey($key)
    {
        return in_array(
            $key,
            [
                'order_id',
                'order_increment_id',
                'order_type',
                'invoice',
                'invoice_id',
                'webhook_id',
                'event_id',
                'event_type',
                'handle',
                'transaction',
                'transaction_id',
                'payment_transaction_id',
                'subscription',
                'subscription_handle',
                'payment_method',
                'payment_methods',
                'is_billwerkplus_payment_method',
                'payment_context',
                'http_errors',
                'updated_urls',
            ],
            true
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    private function isSafeCustomerHandle($key, $value)
    {
        if (!is_scalar($value)) {
            return false;
        }

        // Internal Magento customer entity id is not personal data and is useful for tracing.
        if ($key === 'customer_id') {
            return (bool)preg_match('/^\d+$/', (string)$value);
        }

        return in_array($key, ['customer', 'customer_handle'], true)
            && preg_match('/^cust-[a-z0-9_-]+$/i', (string)$value);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isEmailKey($key)
    {
        return $key === 'email' || substr($key, -6) === '_email';
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isMessageKey($key)
    {
        return in_array($key, ['error_message', 'message'], true);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isEmailValue($value)
    {
        return is_scalar($value)
            && preg_match('/^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$/i', (string)$value);
    }

    /**
     * Identify session references by field and response context.
     *
     * @param string $key
     * @param string $parentKey
     * @return bool
     */
    private function isSessionReferenceKey($key, $parentKey)
    {
        if (in_array($key, ['session_id', 'active_url', 'parent_url', 'uri', 'payment_url', 'payment_info'], true)) {
            return true;
        }

        return $parentKey === 'response' && in_array($key, ['id', 'url'], true);
    }

    /**
     * @param string $key
     * @param string $parentKey
     * @return bool
     */
    private function isCardReferenceKey($key, $parentKey = '')
    {
        return in_array($key, ['card_id', 'recurring_payment_method'], true)
            || ($parentKey === 'cards' && $key === 'id');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function maskCardReference($key, $value)
    {
        if ($key === 'card_id') {
            return $this->maskCardIdentifier($value);
        }

        return $this->maskPaymentMethodIdentifier($value);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function maskSessionField($key, $value)
    {
        if (in_array($key, ['active_url', 'parent_url', 'payment_url', 'payment_info', 'uri', 'url'], true)) {
            return $this->maskSessionUrl($value);
        }

        return $this->maskSessionIdentifier($value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function maskSessionIdentifier($value)
    {
        $value = (string)$value;

        return substr($value, 0, 10) . 'xxxxxxxxxx';
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function maskCardIdentifier($value)
    {
        $value = (string)$value;
        if ($value === '') {
            return self::REDACTED_VALUE;
        }

        return substr($value, 0, 5) . 'xxx';
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function maskPaymentMethodIdentifier($value)
    {
        $value = (string)$value;

        return substr($value, 0, 10) . 'xxxxxxxxxx';
    }

    /**
     * Mask session identifiers embedded in known URL fields.
     *
     * @param mixed $value
     * @return string
     */
    private function maskSessionUrl($value)
    {
        $value = (string)$value;
        $value = preg_replace_callback(
            '/([?&](?:id|session_id|session)=)([^&#]+)/i',
            function ($matches) {
                return $matches[1] . $this->maskSessionIdentifier($matches[2]);
            },
            $value
        );
        $value = preg_replace_callback(
            '/([?&]payment_method=)([^&#]+)/i',
            function ($matches) {
                return $matches[1] . $this->maskPaymentMethodIdentifier($matches[2]);
            },
            $value
        );
        $value = preg_replace_callback(
            '/(#\/checkout\/|\/checkout\/)([^\/?&#]+)/i',
            function ($matches) {
                return $matches[1] . $this->maskSessionIdentifier($matches[2]);
            },
            $value
        );
        // Session id in path, e.g. https://checkout.reepay.com/#/subscription/cs_xxx
        $value = preg_replace_callback(
            '/\bcs_[0-9a-f]{10,}\b/i',
            function ($matches) {
                return $this->maskSessionIdentifier($matches[0]);
            },
            (string)$value
        );
        // Access key in hosted page links, e.g. /subscription/pay/en_GB/<32 hex key>/sub-0001
        $value = preg_replace_callback(
            '/(\/)([0-9a-f]{32})(?=[\/?#&]|$)/i',
            function ($matches) {
                return $matches[1] . $this->maskSessionIdentifier($matches[2]);
            },
            (string)$value
        );

        return $value === null ? (string)$value : $value;
    }

    /**
     * Mask Billwerk+ payment method (ca_) and session (cs_) identifiers found in plain values.
     *
     * @param mixed $value
     * @return mixed
     */
    private function maskKnownIdentifier($value)
    {
        if (is_string($value) && preg_match('/^(?:ca|cs)_[0-9a-f]{16,}$/i', $value)) {
            return $this->maskPaymentMethodIdentifier($value);
        }

        return $value;
    }

    /**
     * Keep a short email prefix and domain for log correlation.
     *
     * @param mixed $email
     * @return string
     */
    private function maskEmail($email)
    {
        $email = (string)$email;
        if (!preg_match('/^([^@\s]+)@([^@\s]+)$/', $email, $matches)) {
            return self::REDACTED_VALUE;
        }

        $localPart = $matches[1];
        $prefix = substr($localPart, 0, min(3, strlen($localPart)));
        if (strlen($localPart) > 3 && substr($localPart, 3, 1) === '_') {
            $prefix .= '_';
        }

        return $prefix . '***@' . $matches[2];
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey($key)
    {
        return (bool)preg_match(
            '/(^|_)(api_key|private_key|password|secret|signature|authorization|access_token|'
                . 'refresh_token|token|card|card_id|card_on_file|fingerprint|gw_ref|exp_date|cvv|cvc|email|phone|first_name|'
                . 'last_name|name|address|address1|address2|company|customer|customer_id|payment_url|source|'
                . 'charge|session|comment|reference|search|order)(_|$)/',
            $key
        );
    }

    /**
     * Remove secrets, emails, card numbers, and session references from messages.
     *
     * @param mixed $message
     * @return string
     */
    private function sanitizeMessage($message)
    {
        $message = (string)$message;
        $sanitizedMessage = preg_replace(
            '/((?:api[_ -]?key|private[_ -]?key|authorization|signature|password|secret|token)'
                . '\s*[:=]\s*)\S+/i',
            '$1' . self::REDACTED_VALUE,
            $message
        );
        if ($sanitizedMessage === null) {
            $sanitizedMessage = $message;
        }

        $sanitizedMessage = preg_replace_callback(
            '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
            function ($matches) {
                return $this->maskEmail($matches[0]);
            },
            $sanitizedMessage
        );
        $sanitizedMessage = preg_replace_callback(
            '/\b(?:ca|cs)_[0-9a-f]{16,}\b/i',
            function ($matches) {
                return $this->maskPaymentMethodIdentifier($matches[0]);
            },
            $sanitizedMessage
        );
        $sanitizedMessage = preg_replace(
            '/\b(?:\d[ -]?){13,19}\b/',
            self::REDACTED_VALUE,
            $sanitizedMessage
        );

        return $this->maskSessionUrl($sanitizedMessage === null ? $message : $sanitizedMessage);
    }
}
