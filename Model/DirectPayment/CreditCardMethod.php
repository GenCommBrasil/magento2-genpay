<?php

namespace GenComm\GenPay\Model\DirectPayment;

use Exception;
use GenComm\GenPay\Helper\Data;
use GenComm\Helper\StringFormat;
use GenComm\Parser\Error;
use GenComm\Parser\GenPay\Transaction\CreditCard;
use GenComm\GenPay\Logger\Logger;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;

/**
 * Class CreditCardMethod
 * @package GenComm\GenPay\Model\DirectPayment
 */
class CreditCardMethod extends PaymentMethod implements Payment
{
    /**
     * CreditCardMethod constructor.
     * @param CountryInformationAcquirerInterface $countryInformation
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ObjectManagerInterface $objectManager
     * @param Order $order
     * @param Data $helper
     * @param Logger $logger
     * @param array $customerPaymentData
     * @throws Exception
     */
    public function __construct(
        CountryInformationAcquirerInterface $countryInformation,
        ScopeConfigInterface $scopeConfigInterface,
        ObjectManagerInterface $objectManager,
        Order $order,
        Data $helper,
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
     * @return \GenComm\Resource\GenPay\CreditCard|\GenComm\Resource\GenPay\PaymentMethod
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
     * @throws Exception
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
     * @throws Exception
     */
    protected function setAdditionInformation(CreditCard $creditCard)
    {
        $this->logger->info("Processing setAdditionInformation.");
        $this->order->getPayment()->setAdditionalInformation('charge_uuid', $creditCard->getChargeId());
        $this->order->getPayment()->setAdditionalInformation('payment_id', $creditCard->getPaymentId());
        $this->order->getPayment()->setAdditionalInformation('installments', $this->customerPaymentData['creditCardInstallment']);
        $this->order->getPayment()->setAdditionalInformation('document', $this->helper->getDocument());
        $this->order->getPayment()->setAdditionalInformation('api_key', $this->helper->getApiKey());
        $this->order->getPayment()->setAdditionalInformation('signature', $this->helper->getSignature());
        $this->order->getPayment()->setCcNumberEnc($creditCard->getCreditCardNum());
        $this->order->getPayment()->setCcType($this->customerPaymentData['creditCardBrand']);
        $this->order->save();
    }
}
