<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema.
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Company payment methods table.
     */
    const COMPANY_PAYMENT_METHOD = 'company_payment';

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'company_payment_method'.
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::COMPANY_PAYMENT_METHOD)
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'identity' => true, 'nullable' => false],
            'Company ID'
        )->addColumn(
            'applicable_payment_method',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Applicable payment method'
        )->addColumn(
            'available_payment_methods',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
            ['nullable' => true],
            'Payment methods list'
        )->addColumn(
            'use_config_settings',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Use config settings'
        )->addForeignKey(
            $installer->getFkName(
                self::COMPANY_PAYMENT_METHOD,
                'company_id',
                \Magento\Company\Setup\InstallSchema::COMPANY_TABLE_NAME,
                'entity_id'
            ),
            'company_id',
            $installer->getTable(\Magento\Company\Setup\InstallSchema::COMPANY_TABLE_NAME),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
