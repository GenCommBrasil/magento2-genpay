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
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        Data $helper,
        Logger $logger
    ) {
        $this->history = $history;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param $post
     */
    public function initialize($post)
    {
        $this->logger->info("Processing initialize in Notification", ['service' => 'WEBHOOK']);
        $this->post = json_decode($post, true);
        $this->getNotificationPost();
        $this->getApprovedDate();
        $this->setNotificationUpdateOrder();
    }

    /**
     * @return bool
     */
    private function setNotificationUpdateOrder()
    {
        $this->logger->info("Processing setNotificationUpdateOrder.", ['service' => 'WEBHOOK']);
        try {
            $incrementId = $this->webhookReference;
            $status = Status::getStatusMapping($this->webhookStatus);
            $this->logger->info("Processing webhook with transaction: " . $incrementId
                . "; State: ". $status . "; Amount: " . $this->amount,
                ['service' => 'WEBHOOK']);

            if (false === $status) {
                $this->logger->info("Cannot process webhook", ['service' => 'WEBHOOK']);
                return false;
            }
            $order = $this->getOrderByIncrementId($incrementId);

            if ($order->canInvoice()) {
                $this->createInvoice($order);
            }

            if ($order->getState() != $status) {
                $history = [
                    'status' => $this->history->setStatus($status),
                    'comment' => $this->history->setComment(__('RakutenPay Notification')),
                ];
                $order->setStatus($status);
                $order->setState($status);
                $order->setStatusHistories($history);
                $order->save();
                $this->logger->info("Update Status Success.", ['service' => 'WEBHOOK']);
                $this->helper->updateStatusRakutenPayOrder($order, $this->webhookStatus);
            }

            return true;
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), ['service' => 'WEBHOOK']);
            return false;
        }
    }

    /**
     * @param $order
     */
    private function createInvoice($order)
    {
        $this->logger->info("Processing createInvoice.", ['service' => 'WEBHOOK']);
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $transaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('Exception Create Invoice: '.$e->getMessage(), false);
            $order->save();
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
        } else if (
            $this->webhookStatus == Status::PARTIAL_REFUNDED ||
            $this->webhookStatus == Status::REFUNDED) {
            $this->amount = -array_sum(array_column($this->post['refunds'], 'amount'));
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
     * @param $incrementId
     * @return mixed
     */
    private function getOrderByIncrementId($incrementId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create('Magento\Sales\Model\Order');
        $order = $collection->loadByIncrementId($incrementId);

        return $order;
    }
}
