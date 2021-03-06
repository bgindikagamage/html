<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Block;

use Magento\Mtf\Block\Form;

/**
 * Class CompanyAccountForm
 * Company account create form
 */
class CompanyAccountForm extends Form
{
    /**
     * Css selector for "Save" button
     *
     * @var string
     */
    protected $saveButton = '.action.save';

    /**
     * Click "Save" button
     *
     * @return void
     */
    public function submit()
    {
        $this->_rootElement->find($this->saveButton)->click();
    }
}
