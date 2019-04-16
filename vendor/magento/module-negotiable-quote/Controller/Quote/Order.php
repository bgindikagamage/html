<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\NegotiableQuote\Controller\Quote;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class Order
 */
class Order extends Quote
{
    /**
     * Order quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');

        try {
            $quote = $this->quoteRepository->get($quoteId);
            if ($quote->getCustomerId() === $this->settingsProvider->getCurrentUserId()) {
                $this->negotiableQuoteManagement->order($quoteId);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                __('We can\'t order the quote right now because of an error: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t order the quote right now.'));
        }

        return $resultRedirect;
    }
}
