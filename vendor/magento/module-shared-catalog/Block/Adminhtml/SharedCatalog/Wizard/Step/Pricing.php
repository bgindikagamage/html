<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Step;

/**
 * Shared catalog Pricing step
 */
class Pricing extends \Magento\Ui\Block\Component\StepsWizard\StepAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getCaption()
    {
        return __('Pricing');
    }
}
