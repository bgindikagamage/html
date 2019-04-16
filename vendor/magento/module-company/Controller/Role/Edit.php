<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Role;

/**
 * Class Edit.
 */
class Edit extends \Magento\Company\Controller\AbstractAction
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * Roles and permissions edit.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Add New Role'));
        if ($this->getRequest()->getParam('id')) {
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Edit Role'));
        }
        $this->_view->renderLayout();
    }
}
