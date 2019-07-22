<?php
namespace Rakuten\RakutenPay\Block\Adminhtml\Order;

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

        $view->addButton(
            'rakutenpay_refund',
            [
                'label' => __('RakutenPay Dashboard'),
                'onclick' => "confirmSetLocation('{$message}', '{$url}')"
            ]
        );
    }
}