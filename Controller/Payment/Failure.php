<?php

namespace Rakuten\RakutenPay\Controller\Payment;

class Failure extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        /** @var  \Magento\Framework\View\Result\PageFactory _resultPageFactory*/
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * Show error page
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var  \Magento\Framework\View\Result\PageFactory $resultPage*/
        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}
