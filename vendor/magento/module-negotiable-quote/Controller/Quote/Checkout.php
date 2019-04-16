<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

/**
 * A proxy class to validate quote items stock status before proceed to checkout
 */
class Checkout extends \Magento\NegotiableQuote\Controller\Quote
{
    /**
     * Authorization level of a company session.
     */
    const NEGOTIABLE_QUOTE_RESOURCE = 'Magento_NegotiableQuote::checkout';

    /**
     * @var \Magento\NegotiableQuote\Model\CheckoutQuoteValidator
     */
    private $checkoutQuoteValidator;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\NegotiableQuote\Model\CheckoutQuoteValidator $checkoutQuoteValidator
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\NegotiableQuote\Model\CheckoutQuoteValidator $checkoutQuoteValidator,
        \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $quoteItemManagement
    ) {
        parent::__construct(
            $context,
            $quoteHelper,
            $quoteRepository,
            $customerRestriction,
            $negotiableQuoteManagement,
            $settingsProvider
        );
        $this->checkoutQuoteValidator = $checkoutQuoteValidator;
        $this->quoteItemManagement = $quoteItemManagement;
    }

    /**
     * View customer quotes actions
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        if (!$quoteId) {
            $quoteId = $this->getRequest()->getParam('negotiableQuoteId');
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/view', ['quote_id' => $quoteId]);

        $quote = $this->quoteRepository->get($quoteId);
        $quote->getExtensionAttributes()->setShippingAssignments(null);
        if ($this->customerRestriction->canSubmit()
            && $quote->getExtensionAttributes()->getNegotiableQuote()->getNegotiatedPriceValue() === null
        ) {
            $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
        }

        $invalidQtyItems = $this->checkoutQuoteValidator->countInvalidQtyItems($quote);
        if ($invalidQtyItems > 0) {
            $message = __(
                '%1 products require your attention. Please contact the Seller if you have any questions.',
                $invalidQtyItems
            );
            $this->messageManager->addError($message);
            return $resultRedirect;
        }

        $resultRedirect->setPath('checkout/index/index', [
            'negotiableQuoteId' => $quoteId
        ]);

        return $resultRedirect;
    }
}
