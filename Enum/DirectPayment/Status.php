<?php

namespace Rakuten\RakutenPay\Enum\DirectPayment;

use Magento\Sales\Model\Order;

/**
 * Class State
 * @package Rakuten\RakutenPay\Enum\DirectPayment
 */
class Status
{
    const PENDING = "pending";
    const AUTHORIZED = "authorized";
    const APPROVED = "approved";
    const COMPLETED = "completed";
    const CANCELLED = "cancelled";
    const REFUNDED = "refunded";
    const PARTIAL_REFUNDED = "partial_refunded";
    const CHARGEBACK = "chargeback";

    private static $statusMapping = [
        self::PENDING => Order::STATE_NEW,
        self::AUTHORIZED => Order::STATE_PENDING_PAYMENT,
        self::APPROVED => Order::STATE_PROCESSING,
        self::COMPLETED => Order::STATE_COMPLETE,
        self::CHARGEBACK => Order::STATE_CANCELED,
        self::CANCELLED => Order::STATE_CANCELED,
        self::REFUNDED => Order::STATE_CLOSED,
        self::PARTIAL_REFUNDED => Order::STATE_PROCESSING,
    ];

    /**
     * @param $status
     * @return bool|string
     */
    public static function getStatusMapping($status)
    {
        return isset(self::$statusMapping[$status]) ? self::$statusMapping[$status] : false;
    }
}
