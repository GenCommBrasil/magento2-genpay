<?php

namespace GenComm\GenPay\Block\Adminhtml\System\Config;

use GenComm\GenPay\Enum\Environment as EnvironmentEnum;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Environment
 * @package GenComm\GenPay\Block\Adminhtml\System\Config
 */
class Environment implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            EnvironmentEnum::SANDBOX => __('Sandbox - Environment for tests'),
            EnvironmentEnum::PRODUCTION => __('Production'),
        ];
    }
}
