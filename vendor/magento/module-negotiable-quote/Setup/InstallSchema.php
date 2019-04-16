<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Install schema for negotiable quote module.
 *
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var string
     */
    private $quoteConnectionName = 'checkout';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'negotiable_quote'
         */
        $table = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote')
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Quote ID'
        )->addColumn(
            'is_regular_quote',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is regular quote'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['unsigned' => true, 'nullable' => false],
            'Negotiable quote status'
        )->addColumn(
            'quote_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Negotiable quote name'
        )->addColumn(
            'negotiated_price_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => true, 'default' => null],
            'Negotiated price type'
        )->addColumn(
            'negotiated_price_value',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            null,
            ['nullable' => true, 'default' => null],
            'Negotiable price value'
        )->addColumn(
            'shipping_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            null,
            ['nullable' => true, 'default' => null],
            'Shipping price'
        )->addColumn(
            'expiration_period',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => true, 'default' => null],
            'Expiration period'
        )->addColumn(
            'status_email_notification',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Status email notification'
        )->addColumn(
            'snapshot',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Snapshot'
        )->addColumn(
            'has_unconfirmed_changes',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Has changes, not confirmed by merchant'
        )->addColumn(
            'is_customer_price_changed',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => 0],
            'Is Customer Price Changed'
        )->addColumn(
            'is_shipping_tax_changed',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => 0],
            'Is Shipping Tax Changed'
        )->addColumn(
            'notifications',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Notifications'
        )->addColumn(
            'applied_rule_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Applied Rule Ids'
        )->addColumn(
            'is_address_draft',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => 0],
            'Is address update from checkout'
        )->addColumn(
            'deleted_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Deleted products SKU'
        )->addColumn(
            'creator_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER],
            'Quote creator type'
        )->addColumn(
            'creator_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Quote creator id'
        )->addColumn(
            'original_total_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Original Total Price'
        )->addColumn(
            'base_original_total_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Base Original Total Price'
        )->addColumn(
            'negotiated_total_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Negotiated Total Price'
        )->addColumn(
            'base_negotiated_total_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Base Negotiated Total Price'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable('negotiable_quote'),
                ['expiration_period']
            ),
            ['expiration_period']
        )->addForeignKey(
            $installer->getFkName(
                'negotiable_quote',
                'quote_id',
                'quote',
                'entity_id'
            ),
            'quote_id',
            $installer->getTable('quote'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($table);

        /**
         * Create table 'negotiable_quote_company_config'
         */
        $companyQuoteConfigTable = $installer->getConnection()->newTable(
            $installer->getTable('negotiable_quote_company_config')
        )->addColumn(
            'company_entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Company ID'
        )->addColumn(
            'is_quote_enabled',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '1'],
            'Is quote enabled'
        )->addForeignKey(
            $installer->getFkName(
                'negotiable_quote_company_config',
                'company_entity_id',
                'company',
                'entity_id'
            ),
            'company_entity_id',
            $installer->getTable('company'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection()->createTable($companyQuoteConfigTable);

        /**
         * Create table 'negotiable_quote_grid'
         */
        $quoteGridTable = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_grid')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'quote_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Quote Name'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Created At'
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Company Id'
        )->addColumn(
            'company_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Company Name'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Customer Id'
        )->addColumn(
            'submitted_by',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Submitted by'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Updated At'
        )->addColumn(
            'sales_rep_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Sales Representative ID'
        )->addColumn(
            'sales_rep',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Sales Representative Name'
        )->addColumn(
            'base_grand_total',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Base Grand Total'
        )->addColumn(
            'grand_total',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Grand Total'
        )->addColumn(
            'base_negotiated_grand_total',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Negotiated Base Grand Total'
        )->addColumn(
            'negotiated_grand_total',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [],
            'Negotiated Grand Total'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Status'
        )->addColumn(
            'base_currency_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Base Currency'
        )->addColumn(
            'quote_currency_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Currency'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Store Id'
        )->addColumn(
            'rate',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['unsigned' => true, 'nullable' => true, 'default' => '1'],
            'Currency Rate'
        )->addIndex(
            $installer->getIdxName('negotiable_quote_grid', ['company_name']),
            ['company_name']
        )->addIndex(
            $installer->getIdxName('negotiable_quote_grid', ['quote_name']),
            ['quote_name']
        )->addIndex(
            $installer->getIdxName('negotiable_quote_grid', ['status']),
            ['status']
        )->addIndex(
            $installer->getIdxName('negotiable_quote_grid', ['updated_at']),
            ['updated_at']
        )->addIndex(
            $installer->getIdxName(
                'negotiable_quote_grid',
                [
                    'company_name',
                    'quote_name'
                ],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            [
                'company_name',
                'quote_name'
            ],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT]
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($quoteGridTable);

        /**
         * Create table 'negotiable_quote_comment'
         */
        $quoteCommentTable = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_comment')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'parent_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Parent Id'
        )->addColumn(
            'creator_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Comment creator type'
        )->addColumn(
            'is_decline',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is quote was declined by seller'
        )->addColumn(
            'is_draft',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is draft message'
        )->addColumn(
            'creator_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Comment author ID'
        )->addColumn(
            'comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Comment'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addIndex(
            $installer->getIdxName('negotiable_quote_comment', ['parent_id']),
            ['parent_id']
        )->addIndex(
            $installer->getIdxName('negotiable_quote_comment', ['created_at']),
            ['created_at']
        )->addForeignKey(
            $installer->getFkName('negotiable_quote_comment', 'parent_id', 'negotiable_quote', 'quote_id'),
            'parent_id',
            $installer->getTable('negotiable_quote'),
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Negotiable quote comments'
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($quoteCommentTable);

        /**
         * Create table 'negotiable_quote_comment_attachment'
         */
        $quoteCommentAttachmentTable = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_comment_attachment')
        )->addColumn(
            'attachment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Attachment Id'
        )->addColumn(
            'comment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Comment Id'
        )->addColumn(
            'file_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Attachment file name'
        )->addColumn(
            'file_path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            4096,
            [],
            'Path to file'
        )->addColumn(
            'file_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'File type'
        )->addIndex(
            $installer->getIdxName('negotiable_quote_comment_attachment', ['comment_id']),
            ['comment_id']
        )->addForeignKey(
            $installer->getFkName(
                'negotiable_quote_comment_attachment',
                'comment_id',
                'negotiable_quote_comment',
                'entity_id'
            ),
            'comment_id',
            $installer->getTable('negotiable_quote_comment'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Negotiable quote comment attachments'
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($quoteCommentAttachmentTable);

        /**
         * Create table 'negotiable_quote_history'
         */
        $quoteHistoryTable = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_history')
        )->addColumn(
            'history_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'History Id'
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Quote Id'
        )->addColumn(
            'is_seller',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is changes has made by seller'
        )->addColumn(
            'author_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Log author ID'
        )->addColumn(
            'is_draft',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '1'],
            'Is draft message'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
                'default' => \Magento\NegotiableQuote\Api\Data\HistoryInterface::STATUS_CREATED
            ],
            'Log status'
        )->addColumn(
            'log_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Serialized log data'
        )->addColumn(
            'snapshot_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Serialized quote snapshot data'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addIndex(
            $installer->getIdxName('negotiable_quote_history', ['quote_id']),
            ['quote_id']
        )->addIndex(
            $installer->getIdxName('negotiable_quote_history', ['created_at']),
            ['created_at']
        )->addForeignKey(
            $installer->getFkName('negotiable_quote_history', 'quote_id', 'negotiable_quote', 'quote_id'),
            'quote_id',
            $installer->getTable('negotiable_quote'),
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Negotiable quote history log'
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($quoteHistoryTable);

        /**
         * Create table 'negotiable_quote_item'
         */
        $negotiableQuoteItem = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_item')
        )->addColumn(
            'quote_item_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Quote Item ID'
        )->addColumn(
            'original_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => '0.0000'],
            'Quote item original price'
        )->addColumn(
            'original_tax_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => '0.0000'],
            'Quote item original price'
        )->addColumn(
            'original_discount_amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false, 'default' => '0.0000'],
            'Quote item original discount'
        )->addForeignKey(
            $installer->getFkName(
                'negotiable_quote_item',
                'quote_item_id',
                'quote_item',
                'item_id'
            ),
            'quote_item_id',
            $installer->getTable('quote_item'),
            'item_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($negotiableQuoteItem);

        /**
         * Create table 'negotiable_quote_purged_content'.
         */
        $negotiableQuotePurgedContentTable = $installer->getConnection($this->quoteConnectionName)->newTable(
            $installer->getTable('negotiable_quote_purged_content')
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Quote ID'
        )->addColumn(
            'purged_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Purged customer related data'
        )->addForeignKey(
            $installer->getFkName(
                'negotiable_quote_purged_content',
                'quote_id',
                'negotiable_quote',
                'quote_id'
            ),
            'quote_id',
            $installer->getTable('negotiable_quote'),
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Negotiable quote purchased content.'
        );
        $installer->getConnection($this->quoteConnectionName)->createTable($negotiableQuotePurgedContentTable);
    }
}
