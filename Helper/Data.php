<?php

namespace Rakuten\RakutenPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Data
 * @package Rakuten\RakutenPay\Helper
 */
class Data extends AbstractHelper
{

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Data constructor.
     * @param ModuleListInterface $moduleList
     * @param Context $context
     */
    public function __construct(
        ModuleListInterface $moduleList,
        Context $context
    ) {
        parent::__construct($context);
        $this->moduleList = $moduleList;
    }

    /**
     * @return null
     */
    public function getVersion()
    {
        $version = $this->moduleList->getOne('Rakuten_RakutenPay');
        if ($version && isset($version['setup_version'])) {
            return $version['setup_version'];
        } else {
            return null;
        }
    }
}
