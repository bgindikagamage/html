<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals;

class Shipping extends AbstractTotals
{
    /**
     * @var string
     */
    protected $code = 'shipping';

    /**
     * Can edit
     *
     * @return bool
     */
    public function canEdit()
    {
        return parent::canEdit()
        && $this->getParentBlock()->getQuote()->getShippingAddress()
        && $this->getParentBlock()->getQuote()->getShippingAddress()->getPostcode();
    }
}
