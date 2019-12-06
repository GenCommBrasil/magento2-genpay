<?php

namespace GenComm\GenPay\Block\Info;

/**
 * Class Billet
 * @package GenComm\GenPay\Block\Info
 */
class Billet extends \Magento\Payment\Block\Info
{
    protected $_template = 'GenComm_GenPay::info/billet.phtml';

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBilletUrl()
    {
        $info = $this->getInfo();
        $billetUrl = $info->getAdditionalInformation('billet_url');

        return $billetUrl;
    }

    /**
     * @return string
     */
    public function getBilletDisplay()
    {
        $billetDisplay = $this->_scopeConfig->getValue('payment/genpay_billet/billet_display', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $billetDisplay;
    }
}
