<?php

namespace Rakuten\RakutenPay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\RakutenPay;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Rakuten\RakutenPay\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /** @var RakutenPay */
    protected $rakutenPay;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleListInterface $moduleList
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList,
        Context $context,
        ManagerInterface $messageManager,
        RemoteAddress $remoteAddress,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->remoteAddress = $remoteAddress;
        $this->storeManager = $storeManager;
        $this->rakutenPay = new RakutenPay(
            $this->getDocument(),
            $this->getApiKey(),
            $this->getSignature(),
            $this->getEnvironment()
        );
    }

    /**
     * @return null
     */
    public function getVersion()
    {
        $version = $this->moduleList->getOne('Rakuten_RakutenPay');
        if ($version && isset($version['setup_version'])) {
            return $version['setup_version'];
        } else {
            return null;
        }
    }

    /**
     * @throws RakutenException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorizationValidate()
    {
        $response = $this->rakutenPay->authorizationValidate();
        if ($response->getMessage() !== true) {
            $errorMsg = __('An error has occurred, please contact us.');
            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
        }

        return $this->rakutenPay;
    }

    /**
     * @return null|string
     */
    public function getEnvironment()
    {
        $environment = $this->scopeConfig->getValue('payment/rakutenpay_configuration/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $environment;
    }

    /**
     * @return null|string
     */
    public function getDocument()
    {
        $document = $this->scopeConfig->getValue('payment/rakutenpay_configuration/document', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $document;
    }

    /**
     * @return null|string
     */
    public function getApiKey()
    {
        $apiKey = $this->scopeConfig->getValue('payment/rakutenpay_configuration/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $apiKey;
    }

    /**
     * @return null|string
     */
    public function getSignature()
    {
        $signature = $this->scopeConfig->getValue('payment/rakutenpay_configuration/signature', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $signature;
    }

    /**
     * @return string
     */
    public function getNotificationURL()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_configuration/notification', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return null|string
     */
    public function getBilletExpiresOn()
    {
        $expiresOn = $this->scopeConfig->getValue('payment/rakutenpay_billet/expiration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $expiresOn;
    }

    /**
     * Check if installments show is enabled
     * @return bool
     */
    public function isInstallments()
    {
        $isEnable = (int) $this->scopeConfig->getValue('payment/rakutenpay_credit_card/installments_active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return ($isEnable == 1) ? true : false;
    }

    /**
     * Check if customer interest is enabled
     * @return bool
     */
    public function isCustomerInterest()
    {
        $isEnable = (int) $this->scopeConfig->getValue('payment/rakutenpay_credit_card/customer_interest', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return ($isEnable == 1) ? true : false;
    }

    /**
     * @return bool
     */
    public function getCustomerInterestMinimum()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_credit_card/customer_interest_minimum_installments', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getMaxInstallmentsQuantity()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_credit_card/maximum_installments_quantity', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getMinimumInstallmentsValue()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_credit_card/minimum_installments', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPayerIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    /**
     * @return RakutenPay
     */
    public function getRakutenPay()
    {
        return $this->rakutenPay;
    }

    /**
     * @param $phone
     * @return array
     */
    public function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $ddd = '';
        if (strlen($phone) > 9) {
            if (substr($phone, 0, 1) == 0) {
                $phone = substr($phone, 1);
            }
            $ddd = substr($phone, 0, 2);
            $phone = substr($phone, 2);
        }

        return array('areaCode' => $ddd, 'number' => $phone);
    }
}
