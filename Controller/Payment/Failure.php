<?php

namespace Rakuten\RakutenPay\Controller\Payment;

use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Failure
 * @package Rakuten\RakutenPay\Controller\Payment
 */
class Failure extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        /** @var  \Magento\Framework\View\Result\PageFactory _resultPageFactory*/
        $this->_resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
    }

    /**
     * Show error page
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $this->logger->info("Processing execute Action in Failure.");
        /** @var  \Magento\Framework\View\Result\PageFactory $resultPage*/
        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}
