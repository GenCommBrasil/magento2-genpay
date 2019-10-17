<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Magento\Framework\Exception\NoSuchEntityException;
use Rakuten\RakutenPay\Enum\DirectPayment\Status;
use Rakuten\RakutenPay\Helper\Data;
use Rakuten\RakutenPay\Logger\Logger;

/**
 * Class Notification
 * @package Rakuten\RakutenPay\Model\Payment
 */
class Notification
{
    /**
     * @var string
     */
    private $webhookReference;

    /**
     * @var string
     */
    private $webhookStatus;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $approvedDate;

    /**
     * @var \Magento\Sales\Api\Data\OrderStatusHistoryInterface
     */
    private $history;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    private $creditmemoService;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var array
     */
    private $post;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Notification constructor.
     * @param \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        Data $helper,
        Logger $logger
    ) {
        $this->history = $history;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $post
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize(\Magento\Sales\Model\Order $order, array $post)
    {
        $this->logger->info("Processing initialize in Notification", ['service' => 'WEBHOOK']);
        $this->order = $order;
        $this->post = $post;
        $this->getNotificationPost();
        $this->getApprovedDate();
        $this->setNotificationUpdateOrder();
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setNotificationUpdateOrder()
    {
        $this->logger->info("Processing setNotificationUpdateOrder.", ['service' => 'WEBHOOK']);
        $this->logger->info(sprintf("Payload Amount: %s", $this->amount), ['service' => 'WEBHOOK']);

        try {
            $incrementId = $this->webhookReference;
            $status = Status::getStatusMapping($this->webhookStatus);
            $this->logger->info(
                "Processing webhook with transaction: " . $incrementId
                . "; State: " . $this->webhookStatus . "; Amount: " . $this->amount,
                ['service' => 'WEBHOOK']
            );

            if (false === $status) {
                $this->logger->info("Cannot process webhook", ['service' => 'WEBHOOK']);
                return false;
            }

            if ($this->webhookStatus == Status::REFUNDED || $this->webhookStatus == Status::PARTIAL_REFUNDED) {
                $this->helper->updateStatusRakutenPayOrder($this->order, $this->webhookStatus);

                return $this->createCreditmemo();
            }

            if ($this->order->canInvoice()) {
                $this->createInvoice();
            }

            if ($this->order->getState() != $status) {
                $history = [
                    'status' => $this->history->setStatus($status),
                    'comment' => $this->history->setComment(__('RakutenPay Notification')),
                ];
                $this->order->setStatus($status);
                $this->order->setState($status);
                $this->order->setStatusHistories($history);
                $this->order->save();
                $this->logger->info("Update Status Success.", ['service' => 'WEBHOOK']);
                $this->helper->updateStatusRakutenPayOrder($this->order, $this->webhookStatus);
            }

            return true;
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), ['service' => 'WEBHOOK']);
            return false;
        }
    }

    /**
     * @throws \Exception
     */
    private function createInvoice()
    {
        $this->logger->info("Processing createInvoice.", ['service' => 'WEBHOOK']);
        try {
            $invoice = $this->invoiceService->prepareInvoice($this->order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $transaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
        } catch (\Exception $e) {
            $this->order->addStatusHistoryComment('Exception Create Invoice: ' . $e->getMessage(), false);
            $this->order->save();
        }
    }

    /**
     * @return void
     */
    private function getNotificationPost()
    {
        $this->logger->info("Processing getNotificationPost.", ['service' => 'WEBHOOK']);
        $this->webhookStatus = $this->post['status'];
        $this->webhookReference = $this->post['reference'];
        if ($this->webhookStatus == Status::APPROVED) {
            $this->amount = floatval($this->post['amount']);
        } elseif (
            $this->webhookStatus == Status::PARTIAL_REFUNDED ||
            $this->webhookStatus == Status::REFUNDED) {
            $this->amount = array_sum(array_column($this->post['refunds'], 'amount'));
        } else {
            $this->amount = false;
        }
    }

    /**
     * @return void
     */
    private function getApprovedDate()
    {
        $this->logger->info("Processing getApprovedDate.", ['service' => 'WEBHOOK']);
        $status = false;
        $key = array_search(Status::APPROVED, array_column($this->post['status_history'], 'status'));
        if (false !== $key) {
            $status = $this->post['status_history'][$key];
        }

        $this->approvedDate = $status['created_at'];
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createCreditmemo()
    {
        $this->logger->info("Processing createCreditmemo.", ['service' => 'WEBHOOK']);
        $creditmemo = $this->creditmemoFactory->createByOrder($this->order);
        $creditmemo->setBaseGrandTotal($this->amount);
        $creditmemo->setBaseSubtotal($this->amount);
        $creditmemo->setSubtotal($this->amount);
        $creditmemo->setGrandTotal($this->amount);
        $this->creditmemoService->refund($creditmemo, true);

        return true;
    }
}
