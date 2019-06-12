<?php

namespace Rakuten\RakutenPay\Controller\Payment;

use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Parser\Error;
use Rakuten\RakutenPay\Enum\PaymentMethod;
use Rakuten\RakutenPay\Model\DirectPayment\BilletMethod;
use Rakuten\RakutenPay\Model\DirectPayment\CreditCardMethod;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Request
 * @package Rakuten\RakutenPay\Controller\Payment
 */
class Request extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var string|int
     */
    private $orderId;

    /** @var \Magento\Framework\Controller\Result\Json  */
    protected $result;

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultJsonFactory;

    /**
     * @var \Rakuten\RakutenPay\Helper\Data 
     */
    protected $rakutenHelper;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Request constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Rakuten\RakutenPay\Helper\Data $rakutenHelper,
        \Magento\Checkout\Model\Session $session,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->result = $this->resultJsonFactory->create();
        $this->rakutenHelper = $rakutenHelper;
        $this->checkoutSession = $session;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info("Processing execute Action in Request.");
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        $result = null;
        try {
            if (is_null($lastRealOrder->getPayment())) {
                $this->logger->error("No order associated.");
                throw new \Magento\Framework\Exception\NotFoundException(__('No order associated.'));
            }

            $paymentData = $lastRealOrder->getPayment()->getData();
            $this->orderId = $lastRealOrder->getId();
            $this->order = $this->loadOrder($this->orderId);
            $this->clearAdditionalInformation();
            if (is_null($this->orderId)) {
                $this->logger->error("There is not order associated with this session.");
                throw new RakutenException("There is not order associated with this session.");
            }

            if ($lastRealOrder->getPayment()->getMethod() === PaymentMethod::BILLET_CODE) {

                $customerPaymentData = [
                    'billetDocument' => $paymentData['additional_information']['billet_document'],
                    'fingerprint' => $paymentData['additional_information']['fingerprint'],
                    'orderId' => $this->orderId
                ];

                $billet = new BilletMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager,
                    $this->order,
                    $this->rakutenHelper,
                    $this->logger,
                    $customerPaymentData
                );
                $result = $billet->createOrder();
            }

            if ($lastRealOrder->getPayment()->getMethod() === PaymentMethod::CREDIT_CARD_CODE) {

                $customerPaymentData = [
                    'fingerprint' => $paymentData['additional_information']['fingerprint'],
                    'creditCardCode' => $paymentData['additional_information']['credit_card_code'],
                    'creditCardHolder' => $paymentData['additional_information']['credit_card_holder'],
                    'creditCardDocument' => $paymentData['additional_information']['credit_card_document'],
                    'creditCardToken' => $paymentData['additional_information']['credit_card_token'],
                    'creditCardBrand' => $paymentData['additional_information']['credit_card_brand'],
                    'creditCardInstallment' => $paymentData['additional_information']['credit_card_installment'],
                    'creditCardInstallmentValue' => $paymentData['additional_information']['credit_card_installment_value'],
                    'creditCardInterestPercent' => $paymentData['additional_information']['creditCard_interest_percent'],
                    'creditCardInterestAmount' => $paymentData['additional_information']['credit_card_interest_amount'],
                    'creditCardInstallmentTotalValue' => $paymentData['additional_information']['credit_card_installment_total_value'],
                    'orderId' => $this->orderId
                ];

                $creditCard = new CreditCardMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager,
                    $this->order,
                    $this->rakutenHelper,
                    $this->logger,
                    $customerPaymentData
                );

                $result = $creditCard->createOrder();
            }

            if ($result instanceof Error) {
                $this->logger->error($result->getMessage());
                $this->cancelOrder($result->getMessage());
                $this->whenError($result->getMessage());
            }

            return $this->_redirect('checkout/onepage/success');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->cancelOrder($exception->getMessage());
            $this->whenError($exception->getMessage());
            return $this->_redirect('rakutenpay/payment/failure');
        }
    }

    /**
     * @param $message
     * @throws \Exception
     */
    private function cancelOrder($message)
    {
        $this->logger->info("Processing cancelOrder.");
        $this->order->cancel();
        $this->order->addCommentToStatusHistory($message);
        $this->order->save();
    }

    /**
     * @void
     */
    private function clearAdditionalInformation()
    {
        $this->logger->info("Processing clearAdditionalInformation.");
        $this->order->getPayment()->unsAdditionalInformation()->getAdditionalInformation();
    }

    /**
     * Return when fails
     *
     * @param $message
     * @return $this
     */

    private function whenError($message)
    {
        $this->logger->info("Processing whenError.");
        return $this->result->setData([
            'success' => false,
            'payload' => [
                'error'    => $message,
                'redirect' => sprintf('%s%s', $this->baseUrl(), 'rakutenpay/payment/failure')
            ]
        ]);
    }

    /**
     * Load a order by id
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrder($orderId)
    {
        $this->logger->info("Processing loadOrder.");
        return $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
    }

    /**
     * Get base url
     *
     * @return string url
     */
    private function baseUrl()
    {
        $this->logger->info("Processing baseUrl.");
        return $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
    }
}
