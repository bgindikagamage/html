<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Company\Login;

/**
 * Company login info block
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * Get create new company url
     *
     * @return string
     */
    public function getCreateCompanyAccountUrl()
    {
        return $this->getUrl('company/account/create');
    }
}
