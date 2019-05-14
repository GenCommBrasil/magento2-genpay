<?php

namespace Rakuten\RakutenPay\Enum;

/**
 * Class Environment
 * @package Rakuten\RakutenPay\Enum
 */
class Environment
{
    /**
     * string
     */
    const SANDBOX = 'sandbox';

    /**
     * string
     */
    const PRODUCTION = 'production';

    /**
     * string
     */
    const RPAY_SANDBOX = "https://static.rakutenpay.com.br/rpayjs/rpay-latest.dev.min.js";

    /**
     * string
     */
    const RPAY_PRODUCTION = "https://static.rakutenpay.com.br/rpayjs/rpay-latest.min.js";
}