<?php

namespace Rakuten\RakutenPay\Controller\Payment;

use Rakuten\Connector\Exception\RakutenException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Rakuten\RakutenPay\Helper\Installment as Installments;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Installment
 * @package Rakuten\RakutenPay\Controller\Payment
 */
class Installment extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Rakuten\RakutenPay\Helper\Data
     */
    protected $rakutenHelper;

    /**
     * @var \Rakuten\RakutenPay\Helper\Installment
     */
    protected $installments;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Installment constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->logger->info("Processing Construct in Installment.", ['service' => 'getInstallments']);
        $this->rakutenHelper = $this->_objectManager->create('Rakuten\RakutenPay\Helper\Data');
        $this->resultJsonFactory = $this->_objectManager->create('Magento\Framework\Controller\Result\JsonFactory');
        $this->installments = new Installments($this->rakutenHelper, $logger);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJsonFactory = $this->resultJsonFactory->create();
        $this->logger->info("Processing execute action.", ['service' => 'getInstallments']);
        try {
            $baseGrandTotal = (float) $this->_request->getParam('baseGrandTotal');
            if (empty($baseGrandTotal)) {
                $this->logger->error("Parameter is missing.", ['service' => 'getInstallments']);
                throw new RakutenException("Parameter is missing.");
            }
            $installments = $this->installments->create($baseGrandTotal);
            $resultJsonFactory->setData($installments);
            $this->logger->info(sprintf("Result: %s", json_encode($installments)), ['service' => 'getInstallments']);

            return $resultJsonFactory;
        } catch (RakutenException $e) {
            $this->logger->error($e->getMessage(), ['service' => 'getInstallments']);
            $resultJsonFactory->setData([
                'error' => [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]
            ]);

            return $resultJsonFactory;
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
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ? bool
    {
        return true;
    }
}