<?php

namespace Rakuten\RakutenPay\Controller\Notification;

use Rakuten\Connector\Enum\Status;
use Rakuten\Connector\Exception\RakutenException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Rakuten\RakutenPay\Helper\Data;
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
     * Webhook constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $this->_objectManager->create('Magento\Framework\Controller\Result\JsonFactory');
        $this->rakutenHelper = $this->_objectManager->create('Rakuten\RakutenPay\Helper\Data');
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $entityBody = $this->_request->getContent();
        $signatureHeader = $this->_request->getHeader('Signature');

        if (empty($entityBody)) {
            $result->setHttpResponseCode(Status::UNPROCESSABLE);
            $result->setData(['error_message' => __('Body is Empty')]);
            return $result;
        }

        $signature = hash_hmac('sha256', $entityBody, $this->rakutenHelper->getSignature(), true);
        $signatureBase64 = base64_encode($signature);

        if (empty($signatureHeader) || $signatureHeader !== $signatureBase64) {
            //TODO Implements log
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
            $result->setData(['error_message' => __('Signature does not match...')]);
            return $result;
        }
        $this->getNotification()->initialize($entityBody);
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
            $this->_objectManager->create('\Magento\Sales\Api\OrderRepositoryInterface'),
            $this->_objectManager->create('\Magento\Sales\Api\Data\OrderStatusHistoryInterface')
        );
    }
}