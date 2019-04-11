<?php

namespace Rakuten\RakutenPay\Block\Frontend;

use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Config;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Rakuten\RakutenPay\Enum\Environment;

class RequireJS extends Template
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getRPay()
    {
        $environment = $this->scopeConfig->getValue('payment/rakutenpay/rakutenpay_configuration/environment');
        //TODO rakuten-sdk-php
        if ($environment == Environment::SANDBOX) {
            return "https://static.rakutenpay.com.br/rpayjs/rpay-latest.dev.min.js";
        }
        return "https://static.rakutenpay.com.br/rpayjs/rpay-latest.min.js";
    }
}