<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\DataConverter\SerializedToJson;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var string
     */
    private $quoteConnectionName = 'checkout';

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
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->convertValuesFromSerializeToJson($setup);
        }
    }

    /**
     * Upgrade to version 2.0.1,
     * Convert data for `quote_id` field in `negotiable_quote` table from php-serialized to JSON format.
     * Convert data for `log_data` and `snapshot_data` fields in `negotiable_quote_history` table
     * from php-serialized to JSON format
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function convertValuesFromSerializeToJson(ModuleDataSetupInterface $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $setup->getConnection($this->quoteConnectionName),
            $setup->getTable('negotiable_quote'),
            'quote_id',
            'snapshot'
        );
        $fieldDataConverter->convert(
            $setup->getConnection($this->quoteConnectionName),
            $setup->getTable('negotiable_quote_history'),
            'history_id',
            'log_data'
        );
        $fieldDataConverter->convert(
            $setup->getConnection($this->quoteConnectionName),
            $setup->getTable('negotiable_quote_history'),
            'history_id',
            'snapshot_data'
        );
    }
}
