<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Setup;

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
     * Company credit table name.
     */
    const COMPANY_CREDIT_TABLE_NAME = 'company_credit';

    /**
     * Company credit history table name.
     */
    const COMPANY_CREDIT_HISTORY_TABLE_NAME = 'company_credit_history';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'company_credit'.
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::COMPANY_CREDIT_TABLE_NAME)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true],
            'Credit ID'
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Company ID'
        )->addColumn(
            'credit_limit',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true, 'nullable' => true],
            'Credit Limit'
        )->addColumn(
            'balance',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => 0],
            'Outstanding balance'
        )->addColumn(
            'currency_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3,
            ['nullable' => false, 'default' => ''],
            'Currency Code'
        )->addColumn(
            'exceed_limit',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' => 0],
            'Exceed Limit'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::COMPANY_CREDIT_TABLE_NAME),
                ['company_id']
            ),
            ['company_id']
        )->addForeignKey(
            $installer->getFkName(self::COMPANY_CREDIT_TABLE_NAME, 'company_id', 'directory_country', 'country_id'),
            'company_id',
            $installer->getTable('company'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Company Credit Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_credit_history'.
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::COMPANY_CREDIT_HISTORY_TABLE_NAME)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true],
            'Credit ID'
        )->addColumn(
            'company_credit_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Company Credit ID'
        )->addColumn(
            'user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'User Id'
        )->addColumn(
            'user_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'User Type'
        )->addColumn(
            'currency_credit',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3,
            ['nullable' => false],
            'Currency Code Credit'
        )->addColumn(
            'currency_operation',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            3,
            ['nullable' => false],
            'Currency Code Operation'
        )->addColumn(
            'rate',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '24,12',
            ['nullable' => false, 'default' => 0],
            'Currency Rate'
        )->addColumn(
            'rate_credit',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '24,12',
            ['nullable' => true, 'default' => 0],
            'Credit Currency Rate'
        )->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => 0],
            'Amount'
        )->addColumn(
            'balance',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => 0],
            'Balance'
        )->addColumn(
            'credit_limit',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => 0],
            'Credit Limit'
        )->addColumn(
            'available_credit',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => 0],
            'Available Credit'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'Type'
        )->addColumn(
            'datetime',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Datetime'
        )->addColumn(
            'purchase_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            60,
            ['nullable' => true, 'default' => null],
            'Purchase order number'
        )->addColumn(
            'comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            500,
            ['nullable' => false, 'default' => ''],
            'Comment'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::COMPANY_CREDIT_HISTORY_TABLE_NAME),
                ['company_credit_id']
            ),
            ['company_credit_id']
        )->addForeignKey(
            $installer->getFkName(
                self::COMPANY_CREDIT_HISTORY_TABLE_NAME,
                'company_credit_id',
                self::COMPANY_CREDIT_TABLE_NAME,
                'entity_id'
            ),
            'company_credit_id',
            $installer->getTable(self::COMPANY_CREDIT_TABLE_NAME),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Company Credit History Table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
