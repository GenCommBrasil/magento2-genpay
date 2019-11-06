<?php

namespace GenComm\GenPay\Block\Frontend;

use GenComm\GenPay\Logger\Logger;

/**
 * Class Success
 * @package GenComm\GenPay\Block
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
     * @var \GenComm\GenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Success constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->order = $this->checkoutSession->getLastRealOrder();
        $this->logger = $logger;
        $this->logger->info("Processing construct in Success.");
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        $this->logger->info("Processing getPaymentMethod.");
        $method = $this->order->getPayment()->getMethod();

        return  $method;
    }

    /**
     * @return string|null
     */
    public function getBilletUrl()
    {
        $this->logger->info("Processing getBilletUrl.");
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();

        if (!count($additionalInformation)) {

            return null;
        }

        return  $additionalInformation['billet_url'];
    }
}