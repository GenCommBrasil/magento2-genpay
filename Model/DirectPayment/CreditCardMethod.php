<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Helper\StringFormat;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\CreditCard;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class CreditCardMethod
 * @package Rakuten\RakutenPay\Model\DirectPayment
 */
class CreditCardMethod extends PaymentMethod implements Payment
{
    /**
     * CreditCardMethod constructor.
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Sales\Model\Order $order
     * @param \Rakuten\RakutenPay\Helper\Data $helper
     * @param Logger $logger
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order $order,
        \Rakuten\RakutenPay\Helper\Data $helper,
        Logger $logger,
        $customerPaymentData = []
    ) {
        parent::__construct(
            $countryInformation,
            $scopeConfigInterface,
            $objectManager,
            $order,
            $helper,
            $logger,
            $customerPaymentData
        );
        $this->logger->info("Processing construct in CreditCardMethod.");
    }

    /**
     * @return \Rakuten\Connector\Resource\RakutenPay\PaymentMethod
     */
    protected function buildPayment()
    {
        $this->logger->info("Processing buildPayment.");
        $creditCard = $this->rakutenPay->asCreditCard()
            ->setReference($this->order->getIncrementId())
            ->setAmount($this->order->getGrandTotal())
            ->setToken($this->customerPaymentData['creditCardToken'])
            ->setBrand($this->customerPaymentData['creditCardBrand'])
            ->setCvv($this->customerPaymentData['creditCardCode'])
            ->setHolderDocument(StringFormat::getOnlyNumbers($this->customerPaymentData['creditCardDocument']))
            ->setHolderName($this->customerPaymentData['creditCardHolder'])
            ->setInstallmentsQuantity($this->customerPaymentData['creditCardInstallment']);

        if ($this->customerPaymentData['creditCardInterestAmount'] > 0) {
            $creditCard->setInstallmentInterest(
                $this->customerPaymentData['creditCardInstallment'],
                $this->customerPaymentData['creditCardInterestPercent'],
                $this->customerPaymentData['creditCardInterestAmount'],
                $this->customerPaymentData['creditCardInstallmentValue'],
                $this->customerPaymentData['creditCardInstallmentTotalValue']
            );
            $creditCard->setAmount($this->customerPaymentData['creditCardInstallmentTotalValue']);
            $this->rakutenPayOrder->setTaxesAmount($this->customerPaymentData['creditCardInterestAmount']);
            $this->rakutenPayOrder->setAmount($this->customerPaymentData['creditCardInstallmentTotalValue']);
        }

        return $creditCard;
    }

    /**
     * @void
     */
    protected function setCreditCardDocument()
    {
        $this->logger->info("Processing setCreditCardDocument.");
        $this->rakutenPayCustomer->setDocument($this->customerPaymentData['creditCardDocument']);
    }

    /**
     * @return false|Error|CreditCard
     * @throws \Exception
     */
    public function createOrder()
    {
        $this->logger->info("Processing createOrder.");
        $this->setCreditCardDocument();

        $response = $this->createRakutenPayOrder();
        if (false === $response) {
            return $response;
        }

        if ($response instanceof CreditCard) {
            $this->setAdditionInformation($response);
        }

        return $response;
    }

    /**
     * @param CreditCard $creditCard
     * @throws \Exception
     */
    protected function setAdditionInformation(CreditCard $creditCard)
    {
        $this->logger->info("Processing setAdditionInformation.");
        $this->order->getPayment()->setAdditionalInformation('charge_uuid', $creditCard->getChargeId());
        $this->order->getPayment()->setAdditionalInformation('installments', $this->customerPaymentData['creditCardInstallment']);
        $this->order->getPayment()->setCcNumberEnc($creditCard->getCreditCardNum());
        $this->order->getPayment()->setCcType($this->customerPaymentData['creditCardBrand']);
        $this->order->save();
    }
}
