<?php
namespace GenComm\GenPay\Model\Config\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use GenComm\GenPay\Enum\PaymentMethod;
use GenComm\GenPay\Logger\Logger;

class Billet implements ConfigProviderInterface
{
    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \GenComm\GenPay\Model\Payment\Billet
     */
    protected $billetMethod;

    /**
     * @var \GenComm\GenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Billet constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance(PaymentMethod::BILLET_CODE);
        $this->scopeConfig = $scopeConfig;
        $this->billetMethod = $paymentHelper->getMethodInstance(PaymentMethod::BILLET_CODE);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'genpay_billet' => [
                    'url' => $this->billetMethod->getCheckoutPaymentUrl(),
                    'instruction' =>  $this->getInstruction(),
                    'due' => $this->getDue(),
                    'title' => $this->getTitle(),
                    'code' => PaymentMethod::BILLET_CODE,
                ],
            ],
        ] : [];
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return $this->scopeConfig->getValue('payment/genpay_billet/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get instruction from config
     *
     * @return string
     */
    protected function getInstruction()
    {
        return nl2br($this->escaper->escapeHtml($this->scopeConfig->getValue('payment/genpay_billet/instruction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)));
    }

    /**
     * Get due from config
     *
     * @return string
     */
    protected function getDue()
    {
        $day = (int)$this->scopeConfig->getValue('payment/genpay_billet/expiration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($day > 1) {
            return nl2br(sprintf(__('Expiration in %s days'), $day));
        } else {
            return nl2br(sprintf(__('Expiration in %s day'), $day));
        }
    }
}
