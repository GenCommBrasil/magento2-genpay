<?php
namespace Rakuten\RakutenPay\Model\Payment;

use Magento\Framework\Exception\NoSuchEntityException;
use Rakuten\RakutenPay\Enum\DirectPayment\Status;
use Rakuten\RakutenPay\Logger\Logger;
use Rakuten\RakutenPay\Model\DirectPayment\PaymentMethod;

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
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

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
     * @param \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history
     * @param \Magento\Framework\App\ResourceConnection $resource,
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history,
        \Magento\Framework\App\ResourceConnection $resource,
        Logger $logger
    ) {
        $this->history = $history;
        $this->resource = $resource;
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
                $this->updateRakutenPayOrder($order);
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
    private function updateRakutenPayOrder($order)
    {
        $this->logger->info('Processing updateRakutenPayOrder.');
        $connection = $this->resource->getConnection();
        try {
            $tableName = $this->resource->getTableName(PaymentMethod::RAKUTENPAY_ORDER);
            $connection->beginTransaction();
            $where = ['entity_id' => $order->getEntityId()];
            $connection->update($tableName, ['status' => $this->webhookStatus], $where);
            $connection->commit();
        } catch(\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => 'Update RakutenPay Order']);
            $connection->rollBack();
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
