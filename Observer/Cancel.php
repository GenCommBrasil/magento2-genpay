<?php
namespace Rakuten\RakutenPay\Observer;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order;
use Rakuten\Connector\Enum\Refund\Requester;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\Refund;
use Rakuten\RakutenPay\Enum\PaymentMethod;
use Rakuten\RakutenPay\Helper\Data;
use Rakuten\RakutenPay\Logger\Logger;

class Cancel implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    public function __construct(
        Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param $rakutenOrder
     * @return bool
     */
    private function isEnvironment($rakutenOrder)
    {
        return $rakutenOrder['environment'] == $this->helper->getEnvironment();
    }

    /**
     * @return bool
     * @throws CouldNotSaveException
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    private function cancel()
    {
        $this->logger->info('Processing cancel in Cancel', ['service' => 'Observer']);
        $rakutenOrder = $this->helper->getRakutenPayOrder($this->order);
        if (count($rakutenOrder)) {
            $rakutenOrder = array_shift($rakutenOrder);
            if (!$this->isEnvironment($rakutenOrder)) {
                $this->logger->error("Error: Order was created in the environment " . $rakutenOrder['environment'], ['service' => 'Observer']);
                throw new CouldNotSaveException(__("Error: Order was created in the environment " . $rakutenOrder['environment']));
            }
            $rakutenPay = $this->helper->getRakutenPay();
            $result = $rakutenPay->cancel($rakutenOrder['charge_uuid'], Requester::MERCHANT, 'Cancel by admin');
            $this->logger->info("Payload: " . $result->getResponse()->getResult(), ['service' => 'Observer']);
            if ($result instanceof Refund) {
                $this->order->cancel();
                $this->order->addCommentToStatusHistory('Cancel by Admin');
                $this->order->save();
                $this->helper->updateStatusRakutenPayOrder($this->order, $result->getStatus());

                return true;
            }
            if ($result instanceof Error) {
                $this->logger->error("HTTP_STATUS: " . $result->getResponse()->getStatus(), ['service' => 'Observer']);
                $this->logger->error("HTTP_RESPONSE: " . $result->getResponse()->getResult(), ['service' => 'Observer']);
                throw new CouldNotSaveException(__($result->getMessage()));
            }
        }

        $this->logger->error("Error: Order not found on table RakutenPayOrder.", ['service' => 'Observer']);
        throw new CouldNotSaveException(__("Error: Order not found on table RakutenPayOrder."));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->logger->info('Processing execute in Cancel.', ['service' => 'Observer']);
        /** @var Order $order */
        $this->order = $observer->getEvent()->getOrder();
        $paymentMethod = $this->order->getPayment()->getMethod();
        if ($paymentMethod == PaymentMethod::CREDIT_CARD_CODE || $paymentMethod == PaymentMethod::BILLET_CODE) {
            $this->cancel();
        }

        return $this;
    }

}