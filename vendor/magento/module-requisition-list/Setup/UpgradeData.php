<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * Constructor
     *
     * @param FieldDataConverterFactory $fieldDataConverterFactory
     */
    public function __construct(
        FieldDataConverterFactory $fieldDataConverterFactory
    ) {
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->upgradeToVersionTwoZeroThree($setup);
        }
    }

    /**
     * Upgrade to version 2.0.3
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function upgradeToVersionTwoZeroThree(ModuleDataSetupInterface $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable('requisition_list_item'),
            'item_id',
            'options'
        );
    }
}
