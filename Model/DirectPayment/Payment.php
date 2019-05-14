<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\Billet;
use Rakuten\Connector\Parser\RakutenPay\Transaction\CreditCard;

interface Payment
{
    /**
     * @return Billet|CreditCard|Error
     */
    public function createOrder();
}
