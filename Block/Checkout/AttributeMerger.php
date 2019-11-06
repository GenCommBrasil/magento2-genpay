<?php

namespace GenComm\GenPay\Block\Checkout;

use GenComm\GenPay\Helper\Data;
use GenComm\GenPay\Logger\Logger;
/**
 * Class AttributeMerger
 * @package GenComm\GenPay\Block\Checkout
 */
class AttributeMerger extends \Magento\Payment\Block\Info
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AttributeMerger constructor.
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(Data $helper, Logger $logger)
    {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param $result
     * @return mixed
     */
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        if (array_key_exists('street', $result)) {
            $result['street']['children'][$this->helper->getStreetPosition()]['placeholder'] = __('Street');
            $result['street']['children'][$this->helper->getStreetNumberPosition()]['placeholder'] = __('Number');
            $result['street']['children'][$this->helper->getStreetComplementPosition()]['placeholder'] = __('Complement');
            $result['street']['children'][$this->helper->getStreetDistrictPosition()]['placeholder'] = __('Neighborhood');
        }

        return $result;
    }
}
