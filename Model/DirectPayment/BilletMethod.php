<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\Billet;

/**
 * Class BilletMethod
 * @package Rakuten\RakutenPay\Model\DirectPayment
 */
class BilletMethod extends PaymentMethod implements Payment
{
    /**
     * Payment constructor.
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Rakuten\RakutenPay\Helper\Data $helper
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order $order,
        \Rakuten\RakutenPay\Helper\Data $helper,
        $customerPaymentData = []
    ) {
        parent::__construct(
            $countryInformation,
            $scopeConfigInterface,
            $objectManager,
            $order,
            $helper,
            $customerPaymentData
        );
    }

    /**
     * @return \Rakuten\Connector\Resource\RakutenPay\PaymentMethod
     */
    protected function buildPayment()
    {
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
        $this->rakutenPayCustomer->setDocument($this->customerPaymentData['billet_document']);
    }

    /**
     * @return false|Error|Billet|\Rakuten\Connector\Parser\RakutenPay\Transaction\CreditCard
     * @throws \Exception
     */
    public function createOrder()
    {
        $this->setBilletDocument();

        $response = $this->createRakutenPayOrder();
        if (false === $response) {
            return $response;
        }

        if ($response instanceof Billet) {
            $this->setAdditionInformation($response);

            return $response;
        }
        $this->cancelOrder($response->getMessage());

        return $response;
    }

    /**
     * @param Billet $billet
     * @throws \Exception
     */
    protected function setAdditionInformation(Billet $billet)
    {
        $this->order->getPayment()->setAdditionalInformation('charge_uuid', $billet->getChargeId());
        $this->order->getPayment()->setAdditionalInformation('billet_url', $billet->getBilletUrl());
        $this->order->getPayment()->setAdditionalInformation('billet', $billet->getBillet());
        $this->order->save();
    }
}
