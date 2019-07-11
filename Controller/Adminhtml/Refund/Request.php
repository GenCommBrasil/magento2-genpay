<?php

namespace Rakuten\RakutenPay\Controller\Adminhtml\Refund;

/**
 * Class Request
 * @package Rakuten\RakutenPay\Controller\Adminhtml\Refund
 */
class Request extends \Magento\Backend\App\Action
{
    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultJsonFactory;
    }

    /**
     * @return Request
     */
    public function execute()
    {
        $refund = new Methods\Refund(
            $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
            $this->_objectManager->create('Magento\Framework\App\ResourceConnection'),
            $this->_objectManager->create('Magento\Framework\Model\ResourceModel\Db\Context'),
            $this->_objectManager->create('Magento\Backend\Model\Session'),
            $this->_objectManager->create('Magento\Sales\Model\Order'),
            $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
            $this->_objectManager->create('UOL\PagSeguro\Helper\Crypt'),
            $this->getRequest()->getParam('days')
        );

        try {
            return $this->whenSuccess($refund->request());
        } catch (\Exception $exception) {
            return $this->whenError($exception->getMessage());
        }
    }
}
