<?php
namespace Rakuten\RakutenPay\Model\Config\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Rakuten\RakutenPay\Enum\PaymentMethod;
use Rakuten\RakutenPay\Logger\Logger;

class CreditCard implements ConfigProviderInterface
{
	/**
     * Years range
     */
    const YEARS_RANGE = 20;

    /**
     * @var \Rakuten\RakutenPay\Model\Payment\CreditCard\
     */
    protected $creditCardMethod;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * CreditCard constructor.
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
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->creditCardMethod = $paymentHelper->getMethodInstance(PaymentMethod::CREDIT_CARD_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'rakutenpay_credit_card' => [
                    'installment_url' => $this->creditCardMethod->getInstallmentUrl(),
                    'year_values' =>  $this->getYearValues(),
                    'url' => $this->creditCardMethod->getCheckoutPaymentUrl(),
                    'title' => $this->getTitle(),
                    'code' => PaymentMethod::CREDIT_CARD_CODE,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getYearValues()
    {
        $data = [];
        $year = idate("Y");
        $maxYear = $year + self::YEARS_RANGE;
        for ($i = $year; $i < $maxYear; $i++) {
            $data[$i] = $i;
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_billet/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}