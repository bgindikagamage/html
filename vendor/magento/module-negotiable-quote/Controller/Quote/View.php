<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Controller\Quote;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;

/**
 * Negotiable Quote View Controller.
 */
class View extends Quote
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RestrictionInterfaceFactory
     */
    private $restrictionFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param RestrictionInterfaceFactory $restrictionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        RestrictionInterfaceFactory $restrictionFactory
    ) {
        parent::__construct(
            $context,
            $quoteHelper,
            $quoteRepository,
            $customerRestriction,
            $negotiableQuoteManagement,
            $settingsProvider
        );

        $this->storeManager = $storeManager;
        $this->restrictionFactory = $restrictionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        try {
            if (!$this->canViewQuote($quoteId)) {
                return $this->processException(__('Requested quote was not found'));
            }
        } catch (NoSuchEntityException $e) {
            return $this->processException(__('Requested quote was not found'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->processException($e->getMessage());
        }
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->getResultPage();
        $resultPage->getConfig()->getTitle()->set(__('Quote'));
        $this->setNavigationBlockActive($resultPage);

        return $resultPage;
    }

    /**
     * Process exception.
     *
     * @param string $message
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function processException($message)
    {
        $this->messageManager->addErrorMessage($message);
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Quote can be viewed.
     *
     * @param int $quoteId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function canViewQuote($quoteId)
    {
        $quote = $this->negotiableQuoteManagement->getNegotiableQuote($quoteId);
        $customerRestriction = $this->restrictionFactory->create($quote);
        $result = $customerRestriction->isOwner() || $customerRestriction->isSubUserContent();

        if ($result) {
            $allowedStoreIds = $this->storeManager->getWebsite()->getStoreIds();
            $result = in_array($quote->getStoreId(), $allowedStoreIds);
        }

        return $result;
    }

    /**
     * Set navigation block active.
     *
     * @param \Magento\Framework\View\Result\Page $resultPage
     * @return void
     */
    private function setNavigationBlockActive(\Magento\Framework\View\Result\Page $resultPage)
    {
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('negotiable_quote/quote');
        }
    }

    /**
     * Check current user permission on resource.
     *
     * @return bool
     */
    protected function isAllowed()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        try {
            $quote = $this->negotiableQuoteManagement->getNegotiableQuote($quoteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return true;
        }

        $customerRestriction = $this->restrictionFactory->create($quote);
        $viewQuotes = $customerRestriction->isAllowed('Magento_NegotiableQuote::view_quotes');
        $viewSubOrdinatesQuotes = $customerRestriction->isAllowed('Magento_NegotiableQuote::view_quotes_sub');
        $isOwner = $customerRestriction->isOwner();

        return $viewQuotes && ($viewSubOrdinatesQuotes || $isOwner);
    }
}
