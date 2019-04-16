<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

/**
 * Class Download
 */
class Download extends \Magento\Backend\App\Action
{
    /**
     * Download provider factory
     *
     * @var \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory
     */
    private $downloadProviderFactory;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory $downloadProviderFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory $downloadProviderFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->downloadProviderFactory = $downloadProviderFactory;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $attachmentId = $this->getRequest()->getParam('attachmentId');
        /** @var \Magento\NegotiableQuote\Model\Attachment\DownloadProvider $downloadProvider */
        $downloadProvider = $this->downloadProviderFactory->create(['attachmentId' => $attachmentId]);

        try {
            $downloadProvider->getAttachmentContents();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\NotFoundException(__('Attachment not found.'));
        }
    }
}
