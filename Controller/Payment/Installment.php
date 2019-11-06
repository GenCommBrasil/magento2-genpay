<?php

namespace GenComm\GenPay\Controller\Payment;

use GenComm\Exception\GenCommException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use GenComm\GenPay\Helper\Installment as Installments;
use GenComm\GenPay\Logger\Logger;

/**
 * Class Installment
 * @package GenComm\GenPay\Controller\Payment
 */
class Installment extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \GenComm\GenPay\Helper\Data
     */
    protected $rakutenHelper;

    /**
     * @var \GenComm\GenPay\Helper\Installment
     */
    protected $installments;

    /**
     * @var \GenComm\GenPay\Logger\Logger
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
        $this->rakutenHelper = $this->_objectManager->create('GenComm\GenPay\Helper\Data');
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
                throw new GenCommException("Parameter is missing.");
            }
            $installments = $this->installments->create($baseGrandTotal);
            $resultJsonFactory->setData($installments);
            $this->logger->info(sprintf("Result: %s", json_encode($installments)), ['service' => 'getInstallments']);

            return $resultJsonFactory;
        } catch (GenCommException $e) {
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
