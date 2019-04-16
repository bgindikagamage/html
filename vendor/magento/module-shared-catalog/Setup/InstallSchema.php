<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Install shared catalog schema.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Shared Catalog table name.
     */
    const SHARED_CATALOG_TABLE_NAME = 'shared_catalog';

    /**
     * Shared Catalog permissions table name.
     */
    const SHARED_CATALOG_PERMISSIONS_TABLE_NAME = 'sharedcatalog_category_permissions';

    /**
     * Shared Catalog Product Item table name.
     */
    const SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME = 'shared_catalog_product_item';

    /**
     * Company table name.
     */
    const COMPANY_TABLE_NAME = 'company';

    /**
     * Customer Group table name.
     */
    const CUSTOMER_GROUP_TABLE_NAME = 'customer_group';

    /**
     * Customer table name.
     */
    const ADMIN_USER_TABLE_NAME = 'admin_user';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $customerGroupTable = $setup->getConnection()->describeTable($setup->getTable('customer_group'));
        $customerGroupIdType = $customerGroupTable['customer_group_id']['DATA_TYPE'] == 'int'
            ? \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER : $customerGroupTable['customer_group_id']['DATA_TYPE'];
        /**
         * Create table 'Shared Catalog table'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::SHARED_CATALOG_TABLE_NAME))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Shared Catalog Entity Id'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Shared Catalog Name'
            )
            ->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Shared Catalog description'
            )
            ->addColumn(
                'customer_group_id',
                $customerGroupIdType,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer Group Id'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1],
                'Type: 0-custom, 1-public'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'created_by',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Customer Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => null],
                'Store ID'
            )
            ->addIndex(
                $installer->getIdxName(self::SHARED_CATALOG_TABLE_NAME, ['store_id']),
                ['store_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    self::SHARED_CATALOG_TABLE_NAME,
                    ['name'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['name'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::SHARED_CATALOG_TABLE_NAME,
                    'created_by',
                    self::ADMIN_USER_TABLE_NAME,
                    'user_id'
                ),
                'created_by',
                $setup->getTable(self::ADMIN_USER_TABLE_NAME),
                'user_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::SHARED_CATALOG_TABLE_NAME,
                    'customer_group_id',
                    self::CUSTOMER_GROUP_TABLE_NAME,
                    'customer_group_id'
                ),
                'customer_group_id',
                $setup->getTable(self::CUSTOMER_GROUP_TABLE_NAME),
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName(self::SHARED_CATALOG_TABLE_NAME, 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Shared Catalog Table');

        $setup->getConnection()->createTable($table);

        /**
         * Create table 'Shared Catalog Product Item table'.
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record Id'
            )
            ->addColumn(
                'customer_group_id',
                $customerGroupIdType,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer Group Id'
            )
            ->addColumn(
                'sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'Product SKU'
            )
            ->addIndex(
                $installer->getIdxName(
                    self::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME,
                    ['sku', 'customer_group_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['sku', 'customer_group_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX]
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME,
                    'customer_group_id',
                    self::CUSTOMER_GROUP_TABLE_NAME,
                    'customer_group_id'
                ),
                'customer_group_id',
                $setup->getTable(self::CUSTOMER_GROUP_TABLE_NAME),
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Shared Catalog Product Item Table');

        $setup->getConnection()->createTable($table);

        /**
         * Create table 'Shared Catalog Permissions table'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::SHARED_CATALOG_PERMISSIONS_TABLE_NAME))
            ->addColumn(
                'permission_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Permission Id'
            )
            ->addColumn(
                'category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Category Id'
            )
            ->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Website Id'
            )
            ->addColumn(
                'customer_group_id',
                $customerGroupIdType,
                null,
                ['unsigned' => true],
                'Customer Group Id'
            )
            ->addColumn(
                'permission',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false],
                'Grant Checkout Items'
            )
            ->addIndex(
                $setup->getIdxName(
                    'magento_sharedcatalogpermissions',
                    ['category_id', 'website_id', 'customer_group_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['category_id', 'website_id', 'customer_group_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName('magento_sharedcatalogpermissions', ['website_id']),
                ['website_id']
            )
            ->addIndex(
                $setup->getIdxName('magento_sharedcatalogpermissions', ['customer_group_id']),
                ['customer_group_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    'magento_sharedcatalogpermissions',
                    'customer_group_id',
                    'customer_group',
                    'customer_group_id'
                ),
                'customer_group_id',
                $setup->getTable('customer_group'),
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName('magento_sharedcatalogpermissions', 'website_id', 'store_website', 'website_id'),
                'website_id',
                $setup->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Shared Catalog Permissions Table');

        $setup->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
