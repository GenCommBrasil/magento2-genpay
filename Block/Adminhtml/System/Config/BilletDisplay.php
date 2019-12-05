<?php

namespace GenComm\GenPay\Block\Adminhtml\System\Config;

use GenComm\GenPay\Enum\Environment as EnvironmentEnum;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class BilletDisplay
 * @package GenComm\GenPay\Block\Adminhtml\System\Config
 */
class BilletDisplay implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            "redirect" => __('Redirect'),
            "tab" => __('New Tab'),
        ];
    }
}
