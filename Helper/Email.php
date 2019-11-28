<?php

namespace GenComm\GenPay\Helper;

use GenComm\GenPay\Logger\Logger;
use GenComm\Parser\GenPay\Transaction\Billet;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\Mail\Template\TransportBuilder;

class Email extends AbstractHelper
{
    /**
     * @var TransportBuilder
     */
    private $emailTransportBuilder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Email constructor.
     * @param Context $context
     * @param TransportBuilder $emailTransportBuilder
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        TransportBuilder $emailTransportBuilder,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->emailTransportBuilder = $emailTransportBuilder;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @param Billet $billet
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendBilletEmail(Order $order, Billet $billet)
    {
        $this->logger->info("Processing sendBilletEmail");
        $this->emailTransportBuilder->addTo($order->getCustomerEmail(), $order->getCustomerName());
        $this->emailTransportBuilder->setTemplateIdentifier('genpay_send_billet_url');
        $this->emailTransportBuilder->setTemplateOptions(
            [
                'area'  => Area::AREA_FRONTEND,
                'store' => $order->getStoreId()
            ]
        );
        $fromName = $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $fromEmail = $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->logger->info(sprintf("Sender: %s <%s>", $fromName, $fromEmail));
        $this->logger->info(sprintf("Order: %s - Billet URL: %s", $order->getIncrementId(), $billet->getBilletUrl()));
        $this->emailTransportBuilder->setFromByScope([
            'name' => $fromName,
            'email' => $fromEmail,
        ]);
        $vars = [
            'order' => $order,
            'paymentMethodTitle' => $this->getPaymentMethodTitle(),
            'billetUrl' => $billet->getBilletUrl(),
        ];
        $this->emailTransportBuilder->setTemplateVars($vars);
        $this->emailTransportBuilder->getTransport()->sendMessage();
    }

    /**
     * @return mixed
     */
    private function getPaymentMethodTitle()
    {
        return $this->scopeConfig->getValue(
            'payment/genpay_billet/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}

