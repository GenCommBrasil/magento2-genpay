<?php

namespace GenComm\GenPay\Block\Adminhtml\System\Config\Form;

use GenComm\GenPay\Helper\Data as CoreHelper;
use GenComm\GenPay\Logger\Logger;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Version
 * @package GenComm\GenPay\Block\Adminhtml\System\Config\Form
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var CoreHelper
     */
    private $coreHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Version constructor.
     * @param Context $context
     * @param CoreHelper $coreHelper
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreHelper = $coreHelper;
        $this->logger = $logger;
        $this->logger->info("Processing construct in Version.");
    }

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element)
    {
        $this->logger->info("Processing render.");
        $version = $this->coreHelper->getVersion();

        if (!$version) {
            $version = __('--');
        }

        $output = '<div style="background-color:#eee;padding:1em;border:1px solid #ddd;">';
        $output .= __('Module version') . ': ' . $version;
        $output .= "</div>";

        return $output;
    }
}
