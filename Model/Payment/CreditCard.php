<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Rakuten\Connector\RakutenPay;
use Rakuten\RakutenPay\Enum\PaymentMethod;

/**
 * Class CreditCard
 * @package Rakuten\RakutenPay\Model\Payment
 */
class CreditCard extends \Magento\Payment\Model\Method\Cc
{
    const DEFAULT_MINIMUM_VALUE = 10.0;
    const DEFAULT_INSTALLMENTS = 1;

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_code = PaymentMethod::CREDIT_CARD_CODE;
    protected $_isGateway               = true;
    protected $_canCapturePartial       = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                = true;
    protected $_canCancel              = true;
    protected $_canUseForMultishipping = false;
    protected $_canReviewPayment = true;
    protected $_countryFactory;
    protected $_supportedCurrencyCodes = ['BRL'];
    protected $_cart;
    protected $_infoBlockType = '\Rakuten\RakutenPay\Block\Info\CreditCard';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Rakuten\RakutenPay\Helper\Data
     */
    protected $rakutenHelper;

    /**
     * @var RakutenPay
     */
    protected $rakutenPay;

    /**
     * @var array
     */
    protected $additionalData = [
        'fingerprint',
        'credit_card_code',
        'credit_card_holder',
        'credit_card_document',
        'credit_card_token',
        'credit_card_brand',
        'credit_card_installment',
        'credit_card_installment_value',
        'creditCard_interest_percent',
        'credit_card_interest_amount',
        'credit_card_installment_total_value',
    ];

    /**
     * CreditCard constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Rakuten\RakutenPay\Helper\Data $rakutenHelper
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Rakuten\RakutenPay\Helper\Data $rakutenHelper
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null
        );
        $this->_countryFactory = $countryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_cart = $cart;
        $this->rakutenHelper = $rakutenHelper;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|\Magento\Payment\Model\Method\Cc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();
        foreach ($this->additionalData as $additionalInfo) {
            if (isset($data->getData('additional_data')[$additionalInfo])) {
                $info->setAdditionalInformation($additionalInfo, $data->getData('additional_data')[$additionalInfo]);
            }
        }

        return $this;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutPaymentUrl()
    {
        return $this->_cart->getQuote()->getStore()->getUrl("rakutenpay/payment/request/");
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInstallmentUrl()
    {
        return $this->_cart->getQuote()->getStore()->getUrl("rakutenpay/payment/installment/");
    }

    /**
     * @return $this|\Magento\Payment\Model\Method\Cc
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    public function validate()
    {
        $this->rakutenPay = $this->rakutenHelper->authorizationValidate();

        return $this;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }
        return true;
    }
}
