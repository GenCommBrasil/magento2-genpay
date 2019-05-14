<?php

namespace Rakuten\RakutenPay\Controller\Payment;

use Rakuten\Connector\Exception\RakutenException;
use Rakuten\RakutenPay\Enum\PaymentMethod;
use Rakuten\RakutenPay\Model\DirectPayment\BilletMethod;

/**
 * Class Request
 * @package Rakuten\RakutenPay\Controller\Payment
 */
class Request extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

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
     * Request constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $this->_objectManager->create('\Magento\Framework\Controller\Result\JsonFactory');
        $this->result = $this->resultJsonFactory->create();
        $this->rakutenHelper = $this->_objectManager->create('Rakuten\RakutenPay\Helper\Data');
        $this->_checkoutSession = $this->_objectManager->create('\Magento\Checkout\Model\Session');
    }

    /**
     * Redirect to payment
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $lastRealOrder = $this->_checkoutSession->getLastRealOrder();

        if (is_null($lastRealOrder->getPayment())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('No order associated.'));
        }

        $paymentData = $lastRealOrder->getPayment()->getData();
        $this->orderId = $lastRealOrder->getId();
        $this->order = $this->loadOrder($this->orderId);
        $this->clearAdditionalInformation();

        if ($lastRealOrder->getPayment()->getMethod() === PaymentMethod::BILLET_CODE) {
            try {

                if (is_null($this->orderId)) {
                    throw new RakutenException("There is not order associated with this session.");
                }

                if (! isset($paymentData['additional_information']['billet_document'])
                    || ! isset($paymentData['additional_information']['fingerprint'])) {
                    throw new RakutenException("Error missing billet_document or fingerprint Request Controller");
                }

                $billet = new BilletMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager,
                    $this->order,
                    $this->rakutenHelper,
                    $data = [
                        'billet_document' => $paymentData['additional_information']['billet_document'],
                        'fingerprint' => $paymentData['additional_information']['fingerprint'],
                        'order_id' => $this->orderId
                    ]
                );
                $billet->createOrder();

                return $this->_redirect('checkout/onepage/success');
            } catch (\Exception $exception) {
                $this->cancelOrder($exception->getMessage());
                $this->whenError($exception->getMessage());
                return $this->_redirect('rakutenpay/payment/failure');
            }
        }
    }

    /**
     * @param $message
     * @throws \Exception
     */
    private function cancelOrder($message)
    {
        $this->order->cancel();
        $this->order->addCommentToStatusHistory($message);
        $this->order->save();
    }

    /**
     * @void
     */
    private function clearAdditionalInformation()
    {
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
        return $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
    }

    /**
     * Get base url
     *
     * @return string url
     */
    private function baseUrl()
    {
        return $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
    }
}
