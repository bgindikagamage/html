<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\NegotiableQuote\Controller\Quote;

/**
 * Class Quote Index Controller
 */
class Index extends Quote
{
    /**
     * Customer quotes
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->getResultPage();
        $resultPage->getConfig()->getTitle()->set(__('My Quotes'));

        return $resultPage;
    }
}
