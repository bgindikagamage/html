<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * Updating quote errors information.
 */
class UpdateErrors extends \Magento\NegotiableQuote\Controller\Adminhtml\Quote
{
    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @param Action\Context $context
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Action\Context $context,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        RawFactory $resultRawFactory
    ) {
        parent::__construct(
            $context,
            $logger,
            $quoteRepository,
            $negotiableQuoteManagement
        );
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Updating quote errors information.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->addHandle('sales_order_create_load_block_json');
        $resultPage->addHandle('quotes_quote_update_load_block_errors');
        $result = $resultPage->getLayout()->renderElement('content');
        return $this->resultRawFactory->create()->setContents($result);
    }
}
