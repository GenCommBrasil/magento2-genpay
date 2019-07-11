<?php

namespace Rakuten\RakutenPay\Controller\Adminhtml\Refund;

/**
 * Class Index
 * @package Rakuten\RakutenPay\Controller\Adminhtml\Refund
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Estorno'));
        $resultPage->getLayout()->getBlock('adminhtml.block.rakutenpay.refund.content')->setData('adminurl', $this->getAdminUrl());
        return $resultPage;
    }

    /**
     * Generate Admin Url
     *
     * @return string
     */
    protected function getAdminUrl()
    {
        return sprintf("%s%s",$this->getBaseUrl(), $this->getAdminSuffix());
    }

    /**
     * Get admin suffix from config
     *
     * @return string
     */
    private function getAdminSuffix()
    {
        $configReader = $this->_objectManager->create('Magento\Framework\App\DeploymentConfig\Reader');
        $config = $configReader->load();
        return $config['backend']['frontName'];
    }

    /**
     * Get store base url
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();
    }

}
