<?php

namespace GenComm\GenPay\Model\DirectPayment;

use GenComm\Enum\Address;
use GenComm\Enum\Category;
use GenComm\Exception\GenCommException;
use GenComm\GenPay;
use GenComm\Helper\StringFormat;
use GenComm\Parser\Error;
use GenComm\Parser\Transaction;
use GenComm\Resource\GenPay\Customer;
use GenComm\Resource\GenPay\Order;
use GenComm\GenPay\Helper\Data;
use GenComm\GenPay\Logger\Logger;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class PaymentMethod
 * @package GenComm\GenPay\Model\DirectPayment
 */
abstract class PaymentMethod
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CountryInformationAcquirerInterface
     */
    protected $countryInformation;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var GenPay
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
     * @var \GenComm\Resource\GenPay\PaymentMethod
     */
    protected $rakutenPayPayment;

    /**
     * @var \GenComm\GenPay\Logger\Logger
     */
    protected $logger;

    /**
     * PaymentMethod constructor.
     * @param CountryInformationAcquirerInterface $countryInformation
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Sales\Model\Order $order
     * @param Data $helper
     * @param Logger $logger
     * @param array $customerPaymentData
     * @throws \Exception
     */
    public function __construct(
        CountryInformationAcquirerInterface $countryInformation,
        ScopeConfigInterface $scopeConfigInterface,
        ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order $order,
        Data $helper,
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
     * @return \GenComm\Resource\GenPay\PaymentMethod
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
        } catch (GenCommException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
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
        } catch (GenCommException $e) {
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

        if (count($address) >= 3) {
            $streetDistrict = $address[$this->helper->getStreetDistrictPosition()];
        }

        if (count($address) == 4) {
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
                $streetComplement
            )
            ->addAddress(
                Address::ADDRESS_SHIPPING,
                $this->order->getShippingAddress()->getPostcode(),
                $street,
                $streetNumber,
                $streetDistrict,
                $this->order->getShippingAddress()->getCity(),
                $this->order->getShippingAddress()->getRegion(),
                $this->order->getShippingAddress()->getName(),
                $streetComplement
            )
            ->addAPhones(
                '55',
                $billingPhone['areaCode'],
                $billingPhone['number'],
                'others',
                Address::ADDRESS_BILLING
            )
            ->addAPhones(
                '55',
                $billingPhone['areaCode'],
                $billingPhone['number'],
                'others',
                Address::ADDRESS_SHIPPING
            );

        return $customer;
    }

    /**
     * @return Order
     * @throws GenCommException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        $order = $this->setItems($order, $this->order->getAllItems());

        return $order;
    }

    /**
     * @param Order $order
     * @param array $items
     * @return Order
     * @throws \GenComm\Exception\GenCommException
     */
    protected function setItems(Order $order, array $items)
    {
        $this->logger->info("Processing setItems.");
        foreach ($items as $item) {
            $order->addItem(
                StringFormat::removeAccents($item->getSku()),
                StringFormat::removeAccents($item->getName()),
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
     * @throws GenCommException
     */
    protected function getCategories($item)
    {
        $this->logger->info("Processing getCategories.");
        $categories = [];
        $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
        $this->logger->info(var_export($product->getCategoryIds(), true) . " Categories Ids.");
        foreach ($product->getCategoryIds() as $categoryId) {
            $category = $this->objectManager->create('Magento\Catalog\Model\Category')->load($categoryId);
            $categories[] = Category::getCategory($categoryId, $category->getName());
        }

        if (count($categories)) {
            return $categories;
        }

        $this->logger->info("Return default category");
        return [
            Category::getDefaultCategory()
        ];
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
            $tableName = $resource->getTableName(Data::GENPAY_ORDER);
            $connection->beginTransaction();
            $connection->insert($tableName, [
                'entity_id' => $this->order->getEntityId(),
                'increment_id' => $this->order->getIncrementId(),
                'charge_uuid' => $transaction->getChargeId(),
                'status' => $transaction->getStatus(),
                'environment' => $this->helper->getEnvironment(),
            ]);
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => 'Save GenPay Order']);
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
