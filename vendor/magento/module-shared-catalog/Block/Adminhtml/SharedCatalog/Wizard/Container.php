<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard;

/**
 * Catalog configure container
 */
class Container extends \Magento\Backend\Block\Template
{
    /**
     * Get wizard html
     * @param array $initData
     * @return string
     */
    public function getWizard(array $initData = [])
    {
        /** @var \Magento\Ui\Block\Component\StepsWizard $wizardBlock */
        $wizardBlock = $this->getChildBlock('catalog-steps-wizard');
        if ($wizardBlock) {
            $wizardBlock->setInitData($initData);
            return $wizardBlock->toHtml();
        }
        return '';
    }
}
