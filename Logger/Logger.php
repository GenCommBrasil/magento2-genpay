<?php
namespace GenComm\GenPay\Logger;

/**
 * Class Logger
 * @package GenComm\GenPay\Logger
 */
class Logger extends \Monolog\Logger
{
    /**
     * @return mixed
     */
    private function isActive()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');

        return $scopeConfig->getValue('payment/genpay_configuration/log', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = [])
    {
        if ($this->isActive()) {
            return $this->addRecord(static::INFO, $message, $context);
        }

        return false;
    }
}
