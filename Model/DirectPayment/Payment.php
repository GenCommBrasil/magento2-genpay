<?php

namespace GenComm\GenPay\Model\DirectPayment;

use GenComm\Parser\Error;
use GenComm\Parser\GenPay\Transaction\Billet;
use GenComm\Parser\GenPay\Transaction\CreditCard;

/**
 * Interface Payment
 * @package GenComm\GenPay\Model\DirectPayment
 */
interface Payment
{
    /**
     * @return Billet|CreditCard|Error
     */
    public function createOrder();
}
