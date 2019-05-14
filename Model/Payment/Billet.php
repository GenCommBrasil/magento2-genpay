<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Rakuten\Connector\RakutenPay;
use Rakuten\RakutenPay\Enum\PaymentMethod;

class Billet extends \Magento\Payment\Model\Method\Cc
{
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_code = PaymentMethod::BILLET_CODE;
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
    protected $_rakutenHelper;
    protected $_infoBlockType = '\Rakuten\RakutenPay\Block\Info\Billet';

    /**
     * @var RakutenPay
     */
    protected $rakutenPay;

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
        $this->_rakutenHelper = $rakutenHelper;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $info = $this->getInfoInstance();

        if (isset($data->getData('additional_data')['billet_document'])) {
            $info->setAdditionalInformation('billet_document', $data->getData('additional_data')['billet_document']);
        }

        if (isset($data->getData('additional_data')['fingerprint'])) {
            $info->setAdditionalInformation('fingerprint', $data->getData('additional_data')['fingerprint']);
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

    public function validate()
    {
        $this->rakutenPay = $this->_rakutenHelper->authorizationValidate();

        return $this;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }
        return true;
    }
}
