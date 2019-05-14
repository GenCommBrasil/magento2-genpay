<?php
namespace Rakuten\RakutenPay\Model\Config\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Rakuten\RakutenPay\Enum\PaymentMethod;

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
     * @var \Rakuten\RakutenPay\Model\Payment\Billet
     */
    protected $billetMethod;

    /**
     * Billet constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance(PaymentMethod::BILLET_CODE);
        $this->scopeConfig = $scopeConfig;
        $this->billetMethod = $paymentHelper->getMethodInstance(PaymentMethod::BILLET_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'rakutenpay_billet' => [
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
        return $this->scopeConfig->getValue('payment/rakutenpay_billet/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get instruction from config
     *
     * @return string
     */
    protected function getInstruction()
    {
        return nl2br($this->escaper->escapeHtml($this->scopeConfig->getValue('payment/rakutenpay_billet/instruction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)));
    }

    /**
     * Get due from config
     *
     * @return string
     */
    protected function getDue()
    {
        $day = (int)$this->scopeConfig->getValue('payment/rakutenpay_billet/expiration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($day > 1) {
            return nl2br(sprintf(__('Expiration in %s days'), $day));
        } else {
            return nl2br(sprintf(__('Expiration in %s day'), $day));
        }
    }
}
