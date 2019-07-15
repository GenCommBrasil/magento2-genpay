<?php

namespace Rakuten\RakutenPay\Controller\Notification;

use Rakuten\Connector\Enum\Status;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Rakuten\RakutenPay\Helper\Data;
use Rakuten\RakutenPay\Logger\Logger;
use Rakuten\RakutenPay\Model\Payment\Notification;

/**
 * Class Webhook
 * @package Rakuten\RakutenPay\Controller\Notification
 */
class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var Data
     */
    protected $rakutenHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */

    protected $resultJsonFactory;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
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
        $this->rakutenHelper = $this->_objectManager->create('Rakuten\RakutenPay\Helper\Data');
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

            $signature = hash_hmac('sha256', $entityBody, $this->rakutenHelper->getSignature(), true);
            $signatureBase64 = base64_encode($signature);

            if (empty($signatureHeader) || $signatureHeader !== $signatureBase64) {
                $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                $result->setData(['error_message' => __('Signature does not match...')]);
                $this->logger->error('Signature does not match...', ['service' => 'WEBHOOK']);
                $this->logger->error(sprintf("Signature Local: %s | Signature Header: %s", $signatureBase64, $signatureHeader), ['service' => 'WEBHOOK']);

                return $result;
            }
            $this->getNotification()->initialize($entityBody);

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
            $resource = $this->_objectManager->create('Magento\Framework\App\ResourceConnection'),
            $this->logger
        );
    }
}