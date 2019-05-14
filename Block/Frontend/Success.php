<?php

namespace Rakuten\RakutenPay\Block\Frontend;

/**
 * Class Success
 * @package Rakuten\RakutenPay\Block
 */
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * Success constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->order = $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        $method = $this->order->getPayment()->getMethod();

        return  $method;
    }

    /**
     * @return string|null
     */
    public function getBilletUrl()
    {
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();

        if (!count($additionalInformation)) {

            return null;
        }

        return  $additionalInformation['billet_url'];
    }
}