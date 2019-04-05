<?php
namespace Rakuten\RakutenPay\Model\Config\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class Billet implements ConfigProviderInterface
{

    /**
     * @var string[]
     */
    protected $methodCode = "rakutenpay_billet";

    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    protected $scopeConfig;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'rakutenpay' => [
                    'rakutenpay_billet' => [
                        'instruction' =>  $this->getInstruction(),
                        'due' => $this->getDue(),
                    ],
                ],
            ],
        ] : [];
    }

    /**
     * Get instruction from config
     *
     * @return string
     */
    protected function getInstruction()
    {
        return nl2br($this->escaper->escapeHtml($this->scopeConfig->getValue("payment/rakuten/rakuten_billet/instruction")));
    }

    /**
     * Get due from config
     *
     * @return string
     */
    protected function getDue()
    {
        $day = (int)$this->scopeConfig->getValue("payment/moipboleto/expiration");
        if ($day > 1) {
            return nl2br(sprintf(__('Expiration in %s days'), $day));
        } else {
            return nl2br(sprintf(__('Expiration in %s day'), $day));
        }
    }
}
