<?php

namespace GenComm\GenPay\Enum;

/**
 * Class Environment
 * @package GenComm\GenPay\Enum
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
    const RPAY_SANDBOX = "https://static.genpay.com.br/rpayjs/rpay-latest.dev.min.js";

    /**
     * string
     */
    const RPAY_PRODUCTION = "https://static.genpay.com.br/rpayjs/rpay-latest.min.js";
}