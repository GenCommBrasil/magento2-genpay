<?php

namespace GenComm\GenPay\Controller\Notification;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use GenComm\Enum\Status;
use GenComm\GenPay\Helper\Data;
use GenComm\GenPay\Logger\Logger;
use GenComm\GenPay\Model\Payment\Notification;

/**
 * Class Webhook
 * @package GenComm\GenPay\Controller\Notification
 */
class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */

    protected $resultJsonFactory;

    /**
     * @var \GenComm\GenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Webhook constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->resultJsonFactory = $this->_objectManager->create('Magento\Framework\Controller\Result\JsonFactory');
        $this->helper = $this->_objectManager->create('GenComm\GenPay\Helper\Data');
    }

    public function execute()
    {
        $this->logger->info("Processing Webhook.");
        $result = $this->resultJsonFactory->create();
        try {
            $entityBody = empty($this->_request->getContent()) ? file_get_contents('php://input') : $this->_request->getContent();
            $signatureHeader = $this->_request->getHeader('Signature');

            if (empty($entityBody)) {
                $result->setHttpResponseCode(Status::UNPROCESSABLE);
                $result->setData(['error_message' => __('Body is Empty')]);
                $this->logger->error('Body is Empty', ['service' => 'WEBHOOK']);
                return $result;
            }

            $post = json_decode($entityBody, true);
            $order = $this->getOrderByIncrementId($post['reference']);
            $signature = $this->getSignature($order);
            $signature = hash_hmac('sha256', $entityBody, $signature, true);
            $signatureBase64 = base64_encode($signature);

            if (empty($signatureHeader) || $signatureHeader !== $signatureBase64) {
                $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                $result->setData(['error_message' => __('Signature does not match...')]);
                $this->logger->error('Signature does not match...', ['service' => 'WEBHOOK']);
                $this->logger->error(sprintf("Signature Local: %s | Signature Header: %s", $signatureBase64, $signatureHeader), ['service' => 'WEBHOOK']);

                return $result;
            }
            $this->getNotification()->initialize($order, $post);
            $this->logger->info(sprintf("Signature Local: %s | Signature Header: %s", $signatureBase64, $signatureHeader), ['service' => 'WEBHOOK']);
            $this->logger->info("Successfully processed.", ['service' => 'WEBHOOK']);

            return $result;
        } catch (\Magento\Framework\Webapi\Exception $e) {
            $this->logger->error($e->getMessage());
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR);
            return $result;
        }
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): ? bool
    {
        return true;
    }

    /**
     * @return Notification
     */
    private function getNotification()
    {
        return new Notification(
            $this->_objectManager->create('\Magento\Sales\Api\Data\OrderStatusHistoryInterface'),
            $this->_objectManager->create('\Magento\Sales\Model\Service\InvoiceService'),
            $this->_objectManager->create('\Magento\Framework\DB\TransactionFactory'),
            $this->_objectManager->create('\Magento\Sales\Model\Order\CreditmemoFactory'),
            $this->_objectManager->create('\Magento\Sales\Model\Service\CreditmemoService'),
            $this->helper,
            $this->logger
        );
    }

    /**
     * @param $incrementId
     * @return \Magento\Sales\Model\Order
     */
    private function getOrderByIncrementId($incrementId)
    {
        $this->logger->info("Processing getOrderByIncrementId.", ['service' => 'WEBHOOK']);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create('Magento\Sales\Model\Order');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $collection->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    private function getSignature(\Magento\Sales\Model\Order $order)
    {
        $this->logger->info("Processing getSignature.", ['service' => 'WEBHOOK']);
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $document = isset($additionalInformation['document']) ? $additionalInformation['document'] : null;
        $apiKey = isset($additionalInformation['api_key']) ? $additionalInformation['api_key'] : null;
        $signature = isset($additionalInformation['signature']) ? $additionalInformation['signature'] : null;
        $this->logger->info(sprintf(
            "Credentials in Order - document: %s - api_key: %s - signature: %s",
            $document,
            $apiKey,
            $signature
        ), ['service' => 'WEBHOOK']);
        $this->logger->info(sprintf(
            "Credentials in Configuration - document: %s - api_key: %s - signature: %s",
            $this->helper->getDocument(),
            $this->helper->getApiKey(),
            $this->helper->getSignature()
        ), ['service' => 'WEBHOOK']);

        if (!is_null($signature)) {
            return $signature;
        }
        return $this->helper->getSignature();
    }
}
