<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Controller\Quote;

/**
 * Class Quote Print Controller
 */
class PrintAction extends Quote
{
    /**
     * @var \Magento\NegotiableQuote\Model\Quote\Address
     */
    private $negotiableQuoteAddress;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\NegotiableQuote\Model\Quote\Address $negotiableQuoteAddress
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\NegotiableQuote\Model\Quote\Address $negotiableQuoteAddress
    ) {
        parent::__construct(
            $context,
            $quoteHelper,
            $quoteRepository,
            $customerRestriction,
            $negotiableQuoteManagement,
            $settingsProvider
        );
        $this->negotiableQuoteAddress = $negotiableQuoteAddress;
    }

    /**
     * Print customer quotes actions
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        $this->negotiableQuoteAddress->updateQuoteShippingAddressDraft($quoteId);
        try {
            if (!$quoteId) {
                throw new NoSuchEntityException();
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__('Requested quote was not found'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->getResultPage();
        $resultPage->getConfig()->getTitle()->set(__('Quote'));

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('negotiable_quote/quote');
        }

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function isAllowed()
    {
        if ($this->customerRestriction->isAllowed('Magento_NegotiableQuote::view_quotes')) {
            if (!$this->customerRestriction->isAllowed('Magento_NegotiableQuote::view_quotes_sub')) {
                $quoteId = $this->getRequest()->getParam('quote_id');
                $this->quoteRepository->get($quoteId);
                return $this->customerRestriction->isOwner();
            }
            return true;
        }
        return false;
    }
}
