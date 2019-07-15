<?php

namespace Rakuten\RakutenPay\Block\Frontend;

class Failure extends \Magento\Framework\View\Element\Template
{
    /**
     * Initialize data and prepare it for output
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Failure constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Element\Template
     */
    protected function _beforeToHtml()
    {
        return parent::_beforeToHtml();
    }
}
