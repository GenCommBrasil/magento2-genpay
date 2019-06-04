<?php

namespace Rakuten\RakutenPay\Controller\Payment;

use Magento\Framework\Controller\Result\JsonFactory;
use Rakuten\Connector\Exception\RakutenException;
use Rakuten\RakutenPay\Helper\Data;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;


use Rakuten\RakutenPay\Helper\Installment as Installments;
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

    protected $request;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Installment constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context

    ) {
        parent::__construct($context);

        $this->rakutenHelper = $this->_objectManager->create('Rakuten\RakutenPay\Helper\Data');
        $this->resultJsonFactory = $this->_objectManager->create('Magento\Framework\Controller\Result\JsonFactory');
        $this->installments = new Installments($this->rakutenHelper);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJsonFactory = $this->resultJsonFactory->create();
        try {
            $baseGrandTotal = (float) $this->_request->getParam('baseGrandTotal');
            if (empty($baseGrandTotal)) {
                throw new RakutenException("Parameter is missing.");
            }
            $installments = $this->installments->create($baseGrandTotal);
            $resultJsonFactory->setData($installments);
            return $resultJsonFactory;

        } catch (RakutenException $e) {
            //TODO Implements Log
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