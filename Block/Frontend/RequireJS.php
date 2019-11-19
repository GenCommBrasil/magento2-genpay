<?php

namespace GenComm\GenPay\Block\Frontend;

use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Config;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use GenComm\GenPay\Enum\Environment;
use GenComm\GenPay\Logger\Logger;

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

    /**
     * @var \GenComm\GenPay\Logger\Logger
     */
    protected $logger;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->logger->info("Processing construct in RequireJS.");
    }

    /**
     * @return string
     */
    public function getRPay()
    {
        $this->logger->info("Processing getRPay.");
        $environment = $this->scopeConfig->getValue('payment/genpay_configuration/environment');
        if ($environment == Environment::SANDBOX) {

            return Environment::RPAY_SANDBOX;
        }

        return Environment::RPAY_PRODUCTION;
    }
}