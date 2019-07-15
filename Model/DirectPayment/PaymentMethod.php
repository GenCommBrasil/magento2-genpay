<?php

namespace Rakuten\RakutenPay\Model\DirectPayment;

use Rakuten\Connector\Enum\Address;
use Rakuten\Connector\Enum\Category;
use Rakuten\Connector\Exception\RakutenException;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\Transaction;
use Rakuten\Connector\Resource\RakutenPay\Customer;
use Rakuten\Connector\Resource\RakutenPay\Order;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class PaymentMethod
 * @package Rakuten\RakutenPay\Model\DirectPayment
 */
abstract class PaymentMethod
{
    /**
     * RakutenPay Table Name
     */
    const RAKUTENPAY_ORDER = 'rakutenpay_order';

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
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * PaymentMethod constructor.
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Sales\Model\Order $order
     * @param \Rakuten\RakutenPay\Helper\Data $helper
     * @param Logger $logger
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order $order,
        \Rakuten\RakutenPay\Helper\Data $helper,
        Logger $logger,
        $customerPaymentData = []
    ) {
        $this->scopeConfig = $scopeConfigInterface;
        $this->countryInformation = $countryInformation;
        $this->objectManager = $objectManager;
        $this->order = $order;
        $this->helper = $helper;
        $this->rakutenPay = $helper->getRakutenPay();
        $this->logger = $logger;
        $this->customerPaymentData = $customerPaymentData;
        $this->logger->info("Processing construct in PaymentMethod.");
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
        $this->logger->info("Processing initialize.");
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
        $this->logger->info("Processing createRakutenPayOrder.");
        $this->logger->info("Payload: ", [
            json_encode($this->rakutenPayOrder->getData(), JSON_PRESERVE_ZERO_FRACTION),
            json_encode($this->rakutenPayCustomer->getData(), JSON_PRESERVE_ZERO_FRACTION),
            json_encode($this->rakutenPayPayment->getData(), JSON_PRESERVE_ZERO_FRACTION),
        ]);
        try {
            $response = $this->rakutenPay->createOrder(
                $this->rakutenPayOrder,
                $this->rakutenPayCustomer,
                $this->rakutenPayPayment
            );
            $this->logger->info("HTTP_STATUS: ", [$response->getResponse()->getStatus()]);
            $this->logger->info("HTTP_RESPONSE: ", [$response->getResponse()->getResult()]);

            if ($response instanceof Error) {

                return $response;
            }
            $this->saveRakutenPayOrder($response);

            return $response;
        } catch (RakutenException $e) {
            $this->logger->error($e->getMessage(), ['service' => 'Create Order']);
            return false;
        }
    }

    /**
     * @return Customer
     * @throws \ReflectionException
     */
    protected function buildCustomer()
    {
        $this->logger->info("Processing buildCustomer.");
        $billingPhone = $this->helper->formatPhone($this->order->getBillingAddress()->getTelephone());
        $address = $this->order->getBillingAddress()->getStreet();
        $street = $address[$this->helper->getStreetPosition()];
        $streetNumber = $address[$this->helper->getStreetNumberPosition()];
        $streetDistrict = $this->order->getBillingAddress()->getRegion();
        $streetComplement = "";

        if(count($address) >= 3) {
            $streetDistrict = $address[$this->helper->getStreetDistrictPosition()];
        }

        if(count($address) == 4){
            $streetComplement = $address[$this->helper->getStreetComplementPosition()];
        }

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
                $street,
                $streetNumber,
                $streetDistrict,
                $this->order->getBillingAddress()->getCity(),
                $this->order->getBillingAddress()->getRegion(),
                $this->order->getBillingAddress()->getName(),
                $streetComplement)
            ->addAddress(Address::ADDRESS_SHIPPING,
                $this->order->getShippingAddress()->getPostcode(),
                $street,
                $streetNumber,
                $streetDistrict,
                $this->order->getShippingAddress()->getCity(),
                $this->order->getShippingAddress()->getRegion(),
                $this->order->getShippingAddress()->getName(),
                $streetComplement)
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
        $this->logger->info("Processing buildOrder.");
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
        $this->logger->info("Processing setItems.");
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
        $this->logger->info("Processing getCategories.");
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
     * @param Transaction $transaction
     */
    protected function saveRakutenPayOrder(Transaction $transaction)
    {
        $this->logger->info('Processing saveRakutenPayOrder.');
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $this->objectManager->create('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        try {
            $tableName = $resource->getTableName(self::RAKUTENPAY_ORDER);
            $connection->beginTransaction();
            $connection->insert($tableName, [
                'entity_id' => $this->order->getEntityId(),
                'increment_id' => $this->order->getIncrementId(),
                'charge_uuid' => $transaction->getChargeId(),
                'status' => $transaction->getStatus(),
                'environment' => $this->helper->getEnvironment(),
            ]);
            $connection->commit();
        } catch(\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => 'Save RakutenPay Order']);
            $connection->rollBack();
        }
    }

    /**
     * @param $discountAmount
     * @return float
     */
    protected function formatDiscountAmount($discountAmount)
    {
        $this->logger->info("Processing formatDiscountAmount.");
        $discountAmount = number_format(abs($discountAmount), 2, ".", "");

        return floatval($discountAmount);
    }

    /**
     * @param $birthDate
     * @return string
     */
    protected function getBirthDate($birthDate)
    {
        $this->logger->info("Processing getBirthDate.");
        return empty($birthDate) ? "2000-10-01 00:00:00" : $birthDate;
    }
}
