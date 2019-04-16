<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Errors;

/**
 * Errors block
 */
class GridContainer extends \Magento\Backend\Block\Template
{
    /**
     * Get error text for validation
     *
     * @return \Magento\Framework\Phrase
     */
    public function getErrorText()
    {
        return __(
            'This action cannot be performed because you have products requiring attention. '
            . 'You must resolve these issues before you can continue.'
        );
    }

    /**
     * Get error title for validation
     *
     * @return \Magento\Framework\Phrase
     */
    public function getErrorTitle()
    {
        return __('Cannot Perform the Requested Action');
    }
}
