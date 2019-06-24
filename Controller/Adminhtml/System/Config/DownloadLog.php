<?php

namespace Rakuten\RakutenPay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Io\File;

class DownloadLog extends Action
{
    CONST FILENAME = 'rakuten.log';

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem\DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $path = $this->directoryList->getPath('log');
        $file = $path . DIRECTORY_SEPARATOR . self::FILENAME;
        if (!file_exists($file)) {
            $resultRaw->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND);

            return $resultRaw;
        }
        $content = file_get_contents($file);

        $resultRaw->setHttpResponseCode(200)
            ->setContents($content)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Length', strlen($content), true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . self::FILENAME . '"', true)
            ->setHeader('Last-Modified', date('r'), true);

        return $resultRaw;
    }
}