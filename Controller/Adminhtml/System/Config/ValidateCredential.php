<?php

namespace GenComm\GenPay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use GenComm\GenPay;
use GenComm\GenPay\Helper\Data;

/**
 * Class ValidateCredential
 * @package GenComm\GenPay\Controller\Adminhtml\System\Config
 */
class ValidateCredential extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var Data
     */
    private $helper;

    /**
     * ValidateCredential constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http $request,
        Data $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->helper = $helper;
    }

    public function execute()
    {
        $document = $this->_request->getParam('taxvat');
        $apiKey = $this->request->getParam('apiKey');
        $signature = $this->request->getParam('signature');
        $environment = $this->request->getParam('environment');

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $message = 'Credenciais validadas com sucesso.';
            $rakutenPay = new GenPay($document, $apiKey, $signature, $environment);
            $response = $rakutenPay->authorizationValidate();

            if ($response->isError()) {
                $message = 'Credenciais inválidas, verifique e tente novamente.';
            }

            $response = [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Credenciais inválidas, verifique e tente novamente.',
            ];
        }

        return $resultJson->setData($response);
    }
}
