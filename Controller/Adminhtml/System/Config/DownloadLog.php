<?php

namespace Rakuten\RakutenPay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;

class DownloadLog extends Action
{
    CONST FILENAME = 'rakuten.log';

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
        if (!$dir->isFile(self::FILENAME)) {
            $this->_response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND);

            return $this->_response;
        }

        $contentLength = $dir->stat(self::FILENAME)['size'];
        $this->_response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Length', $contentLength)
            ->setHeader('Content-Disposition', 'attachment; filename="' . self::FILENAME . '"', true)
            ->setHeader('Last-Modified', date('r'), true);
        $this->_response->sendHeaders();

        $stream = $dir->openFile(self::FILENAME, 'r');
        while (!$stream->eof()) {
            echo $stream->read(1024);
        }
        $stream->close();
        flush();

        return $this->_response;
    }
}