<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Magento\Framework\Exception\NoSuchEntityException;
use Rakuten\RakutenPay\Enum\DirectPayment\Status;

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

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $order,
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history

    ) {
        $this->order = $order;
        $this->history = $history;
    }

    public function initialize($post)
    {
        $this->post = json_decode($post, true);
        $this->getNotificationPost();
        $this->getApprovedDate();
        $this->setNotificationUpdateOrder();
    }

    private function setNotificationUpdateOrder()
    {
        try {
            $incrementId = $this->webhookReference;
            if (false === $this->webhookStatus) {

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
            }

            return true;
        } catch (NoSuchEntityException $e) {
            //TODO implements Log
            return false;
        }
    }

    /**
     * @return void
     */
    private function getNotificationPost()
    {
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
        $status = false;
        $key = array_search(Status::APPROVED, array_column($this->post['status_history'], 'status'));
        if (false !== $key) {

            $status = $this->post['status_history'][$key];
        }

        $this->approvedDate = $status['created_at'];
    }
}
