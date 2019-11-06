<?php
namespace GenComm\GenPay\Observer;

use GenComm\Enum\Refund\Requester;
use GenComm\Parser\Error;
use GenComm\Parser\GenPay\Transaction\Refund as TransationRefund;
use GenComm\Resource\GenPay\Refund as ResourceRefund;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order;
use GenComm\GenPay\Enum\DirectPayment\Status;
use GenComm\GenPay\Enum\PaymentMethod;
use GenComm\GenPay\Helper\Data;
use GenComm\GenPay\Logger\Logger;

/**
 * Class Refund
 * @package GenComm\GenPay\Observer
 */
class Refund implements \Magento\Framework\Event\ObserverInterface
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
     * @var Order\Creditmemo
     */
    private $creditmemo;

    /**
     * @var \GenComm\GenPay
     */
    private $rakutenPay;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * Refund constructor.
     * @param Data $helper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->rakutenPay = $helper->getRakutenPay();
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param $rakutenOrder
     * @throws CouldNotSaveException
     */
    private function validateEnvironment($rakutenOrder)
    {
        if ($rakutenOrder['environment'] != $this->helper->getEnvironment()) {
            $this->logger->error("Error: Order was created in the environment " . $rakutenOrder['environment'], ['service' => 'Observer']);
            throw new CouldNotSaveException(__("Error: Order was created in the environment " . $rakutenOrder['environment']));
        }
    }

    /**
     * Where Billet redirect for Dashboard - Settings Bank Account
     *
     * @throws CouldNotSaveException
     */
    private function validateBillet()
    {
        if ($this->creditmemo->getOrder()->getPayment()->getMethod() == PaymentMethod::BILLET_CODE) {
            $this->logger->error('Refund of the billet is only available on the GenPay Dashboard');
            throw new CouldNotSaveException(__('Refund of the billet is only available on the GenPay Dashboard'));
        }
    }

    /**
     * @param $rakutenOrder
     * @return bool
     */
    private function isNotification($rakutenOrder)
    {
        $this->logger->info('Processing isNotification', ['service' => 'Observer']);
        return $rakutenOrder['status'] == Status::PARTIAL_REFUNDED || $rakutenOrder['status'] == Status::REFUNDED;
    }

    /**
     * @param ResourceRefund $refund
     * @param $chargeId
     * @param $amount
     * @param $refundAmount
     * @return mixed
     * @throws \GenComm\Exception\GenCommException
     */
    private function runRefundRakutenPay(ResourceRefund $refund, $chargeId, $amount, $refundAmount)
    {
        if ($amount == $refundAmount) {
            $this->logger->info('CURL Refund.', ['service' => 'Observer']);
            return $this->rakutenPay->refund($refund, $chargeId);
        }

        $this->logger->info('CURL Partial Refund.', ['service' => 'Observer']);
        return $this->rakutenPay->refundPartial($refund, $chargeId);
    }

    /**
     * @param $amount
     * @param $refundAmount
     * @param $paymentId
     * @return $this
     * @throws CouldNotSaveException
     * @throws \GenComm\Exception\GenCommException
     */
    private function refund($amount, $refundAmount, $paymentId)
    {
        $this->logger->info('Processing refund in Refund', ['service' => 'Observer']);
        $rakutenOrder = $this->helper->getRakutenPayOrder($this->creditmemo->getOrder());
        if (count($rakutenOrder)) {
            $rakutenOrder = array_shift($rakutenOrder);

            if ($this->isNotification($rakutenOrder)) {
                $this->logger->info('isNotification - true', ['service' => 'Observer']);

                return $this;
            }

            $this->validateBillet();
            $this->validateEnvironment($rakutenOrder);
            $refund = $this->rakutenPay->asRefund()
                ->setReason("Refund by Admin")
                ->setRequester(Requester::MERCHANT)
                ->addPayment($paymentId, $refundAmount);

            $result = $this->runRefundRakutenPay($refund, $rakutenOrder['charge_uuid'], $amount, $refundAmount);
            $this->logger->info("Payload: " . $result->getResponse()->getResult(), ['service' => 'Observer']);
            if ($result instanceof TransationRefund) {
                $this->helper->updateStatusRakutenPayOrder($this->creditmemo->getOrder(), $result->getStatus());

                return $this;
            }
            if ($result instanceof Error) {
                $this->logger->error("HTTP_STATUS: " . $result->getResponse()->getStatus(), ['service' => 'Observer']);
                $this->logger->error("HTTP_RESPONSE: " . $result->getResponse()->getResult(), ['service' => 'Observer']);
                throw new CouldNotSaveException(__($result->getMessage()));
            }
        }

        $this->logger->error("Error: Order not found on table GenPayOrder.", ['service' => 'Observer']);
        throw new CouldNotSaveException(__("Error: Order not found on table GenPayOrder."));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->logger->info('Processing execute sales_order_creditmemo_save_after in Refund.', ['service' => 'Observer']);
        $this->creditmemo = $observer->getEvent()->getCreditmemo();
        $refundAmount = (float) $this->creditmemo->getOrder()->getBaseTotalRefunded();
        $amount = (float) $this->creditmemo->getOrder()->getBaseGrandTotal();
        $paymentId = $this->creditmemo->getOrder()->getPayment()->getAdditionalInformation('payment_id');
        $paymentMethod = $this->creditmemo->getOrder()->getPayment()->getMethod();

        if ($paymentMethod == PaymentMethod::CREDIT_CARD_CODE || $paymentMethod == PaymentMethod::BILLET_CODE) {
            if (empty($paymentId)) {
                $this->logger->error("Error: Payment ID is not found.", ['service' => 'Observer']);
                throw new CouldNotSaveException(__("Error: Payment ID is not found."));
            }
            $this->refund($amount, $refundAmount, $paymentId);
        }

        return $this;
    }
}
