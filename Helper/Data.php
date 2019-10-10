<?php

namespace Rakuten\RakutenPay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\RakutenPay;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Data
 * @package Rakuten\RakutenPay\Helper
 */
class Data extends AbstractHelper
{
    /**
     * RakutenPay Table Name
     */
    const RAKUTENPAY_ORDER = 'rakutenpay_order';

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
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleListInterface $moduleList
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param RemoteAddress $remoteAddress
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList,
        Context $context,
        ManagerInterface $messageManager,
        RemoteAddress $remoteAddress,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->remoteAddress = $remoteAddress;
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->rakutenPay = new RakutenPay(
            $this->getDocument(),
            $this->getApiKey(),
            $this->getSignature(),
            $this->getEnvironment()
        );
    }

    /**
     * @param $order
     * @param $status
     */
    public function updateStatusRakutenPayOrder($order, $status)
    {
        $this->logger->info('Processing updateStatusRakutenPayOrder in Data.');
        $connection = $this->resource->getConnection();
        try {
            $tableName = $this->resource->getTableName(Data::RAKUTENPAY_ORDER);
            $connection->beginTransaction();
            $where = ['entity_id = ?' => $order->getEntityId()];
            $connection->update($tableName, ['status' => $status], $where);
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => 'Update Status in RakutenPay Order']);
            $connection->rollBack();
        }
    }

    /**
     * @param $order
     * @return array
     */
    public function getRakutenPayOrder($order)
    {
        $this->logger->info('Processing getRakutenPayOrder in Data.');
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(Data::RAKUTENPAY_ORDER);
        $select = $connection
            ->select()
            ->from($tableName, ['entity_id', 'charge_uuid', 'increment_id', 'status', 'environment'])
            ->where('entity_id = ?', $order->getEntityId());

        return $connection->fetchAssoc($select);
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getNotificationURL()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $notificationUrl = $this->scopeConfig->getValue('payment/rakutenpay_configuration/notification', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $baseUrl . $notificationUrl;
    }

    /**
     * @return string
     */
    public function getStreetPosition()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_configuration/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getStreetNumberPosition()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_configuration/street_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getStreetComplementPosition()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_configuration/street_complement', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getStreetDistrictPosition()
    {
        return $this->scopeConfig->getValue('payment/rakutenpay_configuration/street_district', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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

        return ['areaCode' => $ddd, 'number' => $phone];
    }
}
