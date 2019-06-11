<?php
namespace Rakuten\RakutenPay\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Street
 * @package Rakuten\RakutenPay\Block\Adminhtml\System\Config
 */
class Street implements ArrayInterface
{
    /**
     * @return array
     */
   public function toOptionArray()
    {
        return [
            '0' => '1st Line of the street',
            '1' => '2st Line of the street',
            '2' => '3st Line of the street',
            '3' => '4st Line of the street'
        ];
    }
}