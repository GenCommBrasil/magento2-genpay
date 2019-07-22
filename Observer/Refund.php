<?php
namespace Rakuten\RakutenPay\Observer;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order;
use Rakuten\Connector\Enum\Refund\Requester;
use Rakuten\Connector\Parser\Error;
use Rakuten\Connector\Parser\RakutenPay\Transaction\Refund as TransationRefund;
use Rakuten\RakutenPay\Enum\DirectPayment\Status;
use Rakuten\RakutenPay\Enum\PaymentMethod;
use Rakuten\Connector\Resource\RakutenPay\Refund as ResourceRefund;
use Rakuten\RakutenPay\Helper\Data;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Refund
 * @package Rakuten\RakutenPay\Observer
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
     * @var \Rakuten\Connector\RakutenPay
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
            $this->logger->error('Refund of the billet is only available on the RakutenPay Dashboard');
            throw new CouldNotSaveException(__('Refund of the billet is only available on the RakutenPay Dashboard'));
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
     * @throws \Rakuten\Connector\Exception\RakutenException
     */
    private function executeRefund(ResourceRefund $refund, $chargeId, $amount, $refundAmount)
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
     * @throws \Rakuten\Connector\Exception\RakutenException
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

            $result = $this->executeRefund($refund, $rakutenOrder['charge_uuid'], $amount, $refundAmount);
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

        $this->logger->error("Error: Order not found on table RakutenPayOrder.", ['service' => 'Observer']);
        throw new CouldNotSaveException(__("Error: Order not found on table RakutenPayOrder."));
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