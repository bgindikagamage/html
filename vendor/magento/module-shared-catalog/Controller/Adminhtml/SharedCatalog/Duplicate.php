<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog;

/**
 * Controller for display setting for duplicating shared catalog.
 */
class Duplicate extends AbstractAction
{
    /**
     * View duplicate catalog action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Duplicate of %1', $this->getSharedCatalog()->getName()));

        return $resultPage;
    }
}
