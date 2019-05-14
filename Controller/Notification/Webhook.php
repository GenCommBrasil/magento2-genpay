<?php

namespace Rakuten\RakutenPay\Controller\Notification;

/**
 * Class Webhook
 * @package Rakuten\RakutenPay\Controller\Notification
 */
class Webhook extends \Magento\Framework\App\Action\Action
{
    /**
     * Webhook constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        //TODO implements notification
        echo "<pre>";die(var_dump("TODO"));
    }
}