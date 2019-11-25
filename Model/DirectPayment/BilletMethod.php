<?php

namespace GenComm\GenPay\Model\DirectPayment;

use GenComm\GenPay\Helper\Data;
use GenComm\GenPay\Helper\Email;
use GenComm\Helper\StringFormat;
use GenComm\Parser\Error;
use GenComm\Parser\GenPay\Transaction\Billet;
use GenComm\GenPay\Logger\Logger;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;

/**
 * Class BilletMethod
 * @package GenComm\GenPay\Model\DirectPayment
 */
class BilletMethod extends PaymentMethod implements Payment
{
    /**
     * @var Email
     */
    protected $email;

    /**
     * BilletMethod constructor.
     * @param CountryInformationAcquirerInterface $countryInformation
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ObjectManagerInterface $objectManager
     * @param Order $order
     * @param Data $helper
     * @param Email $email
     * @param Logger $logger
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        CountryInformationAcquirerInterface $countryInformation,
        ScopeConfigInterface $scopeConfigInterface,
        ObjectManagerInterface $objectManager,
        Order $order,
        Data $helper,
        Email $email,
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
        $this->email = $email;
        $this->logger = $logger;
        $this->logger->info("Processing construct in BilletMethod.");
    }

    /**
     * @return \GenComm\Resource\GenPay\Billet|\GenComm\Resource\GenPay\PaymentMethod
     */
    protected function buildPayment()
    {
        $this->logger->info("Processing buildPayment.");
        $billet = $this->rakutenPay->asBillet()
            ->setAmount($this->order->getGrandTotal())
            ->setExpiresOn($this->helper->getBilletExpiresOn());

        return $billet;
    }

    /**
     * @void
     */
    protected function setBilletDocument()
    {
        $this->logger->info("Processing setBilletDocument.");
        $this->rakutenPayCustomer->setDocument(StringFormat::getOnlyNumbers($this->customerPaymentData['billetDocument']));
    }

    /**
     * @return false|Error|Billet
     * @throws \Exception
     */
    public function createOrder()
    {
        $this->logger->info("Processing createOrder.");
        $this->setBilletDocument();

        $response = $this->createRakutenPayOrder();
        if (false === $response) {
            return $response;
        }

        if ($response instanceof Billet) {
            $this->setAdditionInformation($response);
            $this->sendEmail($response);
        }

        return $response;
    }

    /**
     * @param Billet $billet
     * @throws \Exception
     */
    protected function setAdditionInformation(Billet $billet)
    {
        $this->logger->info("Processing setAdditionInformation.");
        $this->order->getPayment()->setAdditionalInformation('charge_uuid', $billet->getChargeId());
        $this->order->getPayment()->setAdditionalInformation('payment_id', $billet->getPaymentId());
        $this->order->getPayment()->setAdditionalInformation('billet_url', $billet->getBilletUrl());
        $this->order->getPayment()->setAdditionalInformation('billet', $billet->getBillet());
        $this->order->getPayment()->setAdditionalInformation('document', $this->helper->getDocument());
        $this->order->getPayment()->setAdditionalInformation('api_key', $this->helper->getApiKey());
        $this->order->getPayment()->setAdditionalInformation('signature', $this->helper->getSignature());
        $this->order->save();
    }

    /**
     * @param Billet $billet
     * @throws \Exception
     */
    protected function sendEmail(Billet $billet)
    {
        $this->logger->info("Processing sendEmail in BilletMethod.");
        $this->email->sendBilletEmail($this->order, $billet);
    }
}
