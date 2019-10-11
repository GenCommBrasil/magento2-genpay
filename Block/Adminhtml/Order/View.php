<?php
namespace Rakuten\RakutenPay\Block\Adminhtml\Order;

use Rakuten\RakutenPay\Enum\PaymentMethod;

/**
 * Class View
 * @package Rakuten\RakutenPay\Block\Adminhtml\Order
 */
class View
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $view
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $message = __('You will be redirected to RakutenPay Dashboard click OK to continue');
        $chargeId = $view->getOrder()->getPayment()->getAdditionalInformation('charge_uuid');
        $url = 'https://dashboard.rakutenpay.com.br/sales/' . $chargeId;

        if (self::isRakutenPayOrder($view->getOrder())) {
            /** Remove Button Cancel in Order */
            $view->removeButton("order_cancel");
            /** Add RakutenPay Dashboard Button */
            $view->addButton(
                'rakutenpay_refund',
                [
                    'label' => __('RakutenPay Dashboard'),
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')"
                ]
            );
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public static function isRakutenPayOrder(\Magento\Sales\Model\Order $order)
    {
        return $order->getPayment()->getMethod() == PaymentMethod::BILLET_CODE ||
            $order->getPayment()->getMethod() == PaymentMethod::CREDIT_CARD_CODE;
    }
}
