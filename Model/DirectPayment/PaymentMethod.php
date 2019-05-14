<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Enum\Address;
use Rakuten\Connector\Enum\Category;
use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Resource\RakutenPay\Customer;
use Rakuten\Connector\Resource\RakutenPay\Order;

/**
 * Class PaymentMethod
 * @package Rakuten\RakutenPay\Model\DirectPayment
 */
abstract class PaymentMethod
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $countryInformation;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Rakuten\RakutenPay\Helper\Data
     */
    protected $helper;

    /**
     * @var \Rakuten\Connector\RakutenPay
     */
    protected $rakutenPay;

    /**
     * @var array
     */
    protected $customerPaymentData;

    /**
     * @var Customer
     */
    protected $rakutenPayCustomer;

    /**
     * @var Order
     */
    protected $rakutenPayOrder;

    /**
     * @var \Rakuten\Connector\Resource\RakutenPay\PaymentMethod
     */
    protected $rakutenPayPayment;

    /**
     * Payment constructor.
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Rakuten\RakutenPay\Helper\Data $helper
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order $order,
        \Rakuten\RakutenPay\Helper\Data $helper,
        $customerPaymentData = []
    ) {
        $this->scopeConfig = $scopeConfigInterface;
        $this->countryInformation = $countryInformation;
        $this->objectManager = $objectManager;
        $this->order = $order;
        $this->helper = $helper;
        $this->rakutenPay = $helper->getRakutenPay();
        $this->customerPaymentData = $customerPaymentData;
        $this->initialize();
    }

    /**
     * @return \Rakuten\Connector\Resource\RakutenPay\PaymentMethod
     */
    abstract protected function buildPayment();

    /**
     * Setting Default Values
     *
     * @throws \Exception
     */
    protected function initialize()
    {
        try {
            $this->rakutenPayCustomer = $this->buildCustomer();
            $this->rakutenPayOrder = $this->buildOrder();
            $this->rakutenPayPayment = $this->buildPayment();
        } catch (RakutenException $e) {
            throw $e;
        }
    }

    /**
     * @return \Rakuten\Connector\Parser\RakutenPay\Transaction\Billet|\Rakuten\Connector\Parser\RakutenPay\Transaction\CreditCard|\Rakuten\Connector\Parser\Error|false
     */
    protected function createRakutenPayOrder()
    {
        try {
            $response = $this->rakutenPay->createOrder(
                $this->rakutenPayOrder,
                $this->rakutenPayCustomer,
                $this->rakutenPayPayment
            );

            return $response;
        } catch (RakutenException $e) {

            return false;
        }
    }

    /**
     * @return \Rakuten\Connector\Resource\RakutenPay\Customer
     */
    protected function buildCustomer()
    {
        $billingPhone = $this->helper->formatPhone($this->order->getBillingAddress()->getTelephone());

        $customer = $this->rakutenPay->customer()
            ->setName($this->order->getCustomerName())
            ->setKind('personal')
            ->setEmail($this->order->getCustomerEmail())
            ->setDocument($this->order->getCustomerTaxvat())
            ->setBusinessName($this->order->getCustomerName())
            ->setBirthDate($this->getBirthDate($this->order->getCustomerDob()))
            ->addAddress(
                Address::ADDRESS_BILLING,
                $this->order->getBillingAddress()->getPostcode(),
                $this->formatStreet($this->order->getBillingAddress()->getStreet()),
                "__",
                $this->order->getBillingAddress()->getRegion(),
                $this->order->getBillingAddress()->getCity(),
                $this->order->getBillingAddress()->getRegion(),
                $this->order->getBillingAddress()->getName())
            ->addAddress(Address::ADDRESS_SHIPPING,
                $this->order->getShippingAddress()->getPostcode(),
                $this->formatStreet($this->order->getShippingAddress()->getStreet()),
                "__",
                $this->order->getShippingAddress()->getRegion(),
                $this->order->getShippingAddress()->getCity(),
                $this->order->getShippingAddress()->getRegion(),
                $this->order->getShippingAddress()->getName())
            ->addAPhones('55',
                $billingPhone['areaCode'],
                $billingPhone['number'],
                'others',
                Address::ADDRESS_BILLING)
            ->addAPhones('55',
                $billingPhone['areaCode'],
                $billingPhone['number'],
                'others',
                Address::ADDRESS_SHIPPING);

        return $customer;
    }

    /**
     * @return Order
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    protected function buildOrder()
    {
        $order = $this->rakutenPay->order()
            ->setWebhookUrl($this->helper->getNotificationURL())
            ->setReference($this->order->getIncrementId())
            ->setFingerprint($this->customerPaymentData['fingerprint'])
            ->setPayerIp($this->helper->getPayerIp())
            ->setCurrency("BRL")
            ->setAmount($this->order->getGrandTotal())
            ->setItemsAmount($this->order->getBaseSubtotal())
            ->setDiscountAmount($this->formatDiscountAmount($this->order->getDiscountAmount()))
            ->setShippingAmount($this->order->getShippingAmount())
            ->setTaxesAmount($this->order->getTaxAmount());
        $order = $this->setItems($order, $this->order->getAllVisibleItems());

        return $order;
    }

    /**
     * @param Order $order
     * @param array $items
     * @return Order
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    protected function setItems(Order $order, array $items)
    {
        foreach ($items as $item) {
            $order->addItem(
                $item->getSku(),
                $item->getName(),
                (int) $item->getQtyOrdered(),
                (float) $item->getPrice(),
                $item->getRowTotalInclTax(),
                $this->getCategories($item)
            );
        }

        return $order;
    }

    /**
     * @param $item
     * @return array
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    protected function getCategories($item)
    {
        $categories = [];
        $product = $this->objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());
        foreach ($product->getCategoryIds() as $id) {
            $category = $this->objectManager->get('Magento\Catalog\Model\Category')->load($id);
            $categories[] = Category::getCategory($id, $category->getName());
        }

        if (count($categories)) {

            return $categories;
        }

        return Category::getDefaultCategory();
    }

    /**
     * @param $discountAmount
     * @return float
     */
    protected function formatDiscountAmount($discountAmount)
    {
        $discountAmount = number_format(abs($discountAmount), 2, ".", "");

        return floatval($discountAmount);
    }

    /**
     * @param $birthDate
     * @return string
     */
    protected function getBirthDate($birthDate)
    {
        return empty($birthDate) ? "2000-10-01 00:00:00" : $birthDate;
    }

    /**
     * @param $street
     * @return string
     */
    protected function formatStreet($street)
    {
        if (is_array($street)) {
            return implode(", ", $street);
        }

        return $street;
    }

    //TODO Implements - CreditCard
    protected function getTaxesAmount()
    {
        return '';
    }
}
