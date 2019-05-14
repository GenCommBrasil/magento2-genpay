<?php

namespace Rakuten\RakutenPay\Block\Info;

/**
 * Class Billet
 * @package Rakuten\RakutenPay\Block\Info
 */
class Billet extends \Magento\Payment\Block\Info
{
    protected $_template = 'Rakuten_RakutenPay::info/billet.phtml';

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
}
