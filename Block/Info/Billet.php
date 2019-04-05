<?php

namespace Rakuten\RakutenPay\Block\Info;

class Billet extends \Magento\Payment\Block\Info
{

    protected $_template = 'Moip_Magento2::info/boleto.phtml';


    public function getLinkPay(){
        $_info = $this->getInfo();
        $transactionId = $_info->getAdditionalInformation('href_boleto');

        return $transactionId;
    }

    public function getLinkPrintPay(){
        $_info = $this->getInfo();
        $transactionId = $_info->getAdditionalInformation('href_boleto_print');

        return $transactionId;
    }

    public function getLineCodeBoleto(){
        $_info = $this->getInfo();
        $transactionId = $_info->getAdditionalInformation('line_code_boleto');

        return $transactionId;
    }

    public function getExpirationDateBoleto(){
        $_info = $this->getInfo();
        $transactionId = $_info->getAdditionalInformation('expiration_date_boleto');

        return $transactionId;
    }



}
