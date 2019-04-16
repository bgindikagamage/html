<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Block\Adminhtml\Customer;

use Magento\Mtf\Block\Block;

/**
 * Class EditAddresses
 */
class EditAddresses extends Block
{
    /**
     * Delete address css selector
     *
     * @var string
     */
    protected $deleteAddressLinkSelector = '.action-delete';

    /**
     * Delete default address
     */
    public function deleteDefaultAddress()
    {
        $this->waitForElementVisible($this->deleteAddressLinkSelector);
        $this->_rootElement->find($this->deleteAddressLinkSelector)->click();
    }
}
