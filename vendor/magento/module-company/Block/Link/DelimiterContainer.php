<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Block\Link;

use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * DelimiterContainer for account navigation.
 */
class DelimiterContainer extends \Magento\Framework\View\Element\Template implements SortLinkInterface
{
    /**
     * If at least one child has HTML body we return it.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = '';
        foreach ($this->getChildNames() as $childName) {
            if ($this->getChildHtml($childName) != '') {
                $html = $this->getChildHtml($childName);
            }
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
}
