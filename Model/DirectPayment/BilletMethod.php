<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Helper\StringFormat;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\Billet;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class BilletMethod
 * @package Rakuten\RakutenPay\Model\DirectPayment
 */
class BilletMethod extends PaymentMethod implements Payment
{
    /**
     * BilletMethod constructor.
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
        $this->logger = $logger;
        $this->logger->info("Processing construct in BilletMethod.");
    }

    /**
     * @return \Rakuten\Connector\Resource\RakutenPay\PaymentMethod
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
        $this->order->save();
    }
}
