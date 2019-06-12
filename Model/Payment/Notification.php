<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Magento\Framework\Exception\NoSuchEntityException;
use Rakuten\RakutenPay\Enum\DirectPayment\Status;
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $order;

    /**
     * @var \Magento\Sales\Api\Data\OrderStatusHistoryInterface
     */
    private $history;

    /**
     * @var array
     */
    private $post;

    /**
     * @var \Rakuten\RakutenPay\Logger\Logger
     */
    protected $logger;

    /**
     * Notification constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $order
     * @param \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $order,
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history,
        Logger $logger


    ) {
        $this->order = $order;
        $this->history = $history;
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
            $this->logger->info("Processing webhook with transaction: " . $incrementId
                . "; State: ". $this->webhookStatus . "; Amount: " . $this->amount,
                ['service' => 'WEBHOOK']);

            if (false === $this->webhookStatus) {
                $this->logger->info("Cannot process webhook", ['service' => 'WEBHOOK']);
                return false;
            }
            $order = $this->order->get($incrementId);

            if ($order->getState() != $this->webhookStatus) {
                $history = [
                    'status' => $this->history->setStatus($this->webhookStatus),
                    'comment' => $this->history->setComment(__('RakutenPay Notification')),
                ];
                $order->setStatus($this->webhookStatus);
                $order->setState($this->webhookStatus);
                $order->setStatusHistories($history);
                $order->save();
                $this->logger->info("Update Status Success.", ['service' => 'WEBHOOK']);
            }

            return true;
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), ['service' => 'WEBHOOK']);
            return false;
        }
    }

    /**
     * @return void
     */
    private function getNotificationPost()
    {
        $this->logger->info("Processing getNotificationPost.", ['service' => 'WEBHOOK']);
        $this->webhookStatus = Status::getStatusMapping($this->post['status']);
        $this->webhookReference = $this->post['reference'];
        if ($this->webhookStatus == 'approved') {
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
}
