<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;

/**
 * Company install schema.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Company table name.
     */
    const COMPANY_TABLE_NAME = 'company';

    /**
     * Team table name.
     */
    const TEAM_TABLE_NAME = 'company_team';

    /**
     * Structure table name.
     */
    const STRUCTURE_TABLE_NAME = 'company_structure';

    /**
     * Advanced customer entity table name.
     */
    const ADVANCED_CUSTOMER_ENTITY_TABLE_NAME = 'company_advanced_customer_entity';

    /**
     * Orders extension attributes table name.
     */
    const ORDER_ENTITY_TABLE_NAME = 'company_order_entity';

    /**
     * Customer entity table name.
     */
    const CUSTOMER_GRID_FLAT_TABLE_NAME = 'customer_grid_flat';

    /**
     * Directory country region table name.
     */
    const DIRECTORY_COUNTRY_REGION_TABLE_NAME = 'directory_country_region';

    /**
     * Roles table name.
     */
    const ROLES_TABLE_NAME = 'company_roles';

    /**
     * User roles table name.
     */
    const USER_ROLES_TABLE_NAME = 'company_user_roles';

    /**
     * Permissions table name.
     */
    const PERMISSIONS_TABLE_NAME = 'company_permissions';

    /**
     * @var string
     */
    private $salesConnectionName = 'sales';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $customerGroupTable = $setup->getConnection()->describeTable($setup->getTable('customer_group'));
        $customerGroupIdType = $customerGroupTable['customer_group_id']['DATA_TYPE'] == 'int'
            ? \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER : $customerGroupTable['customer_group_id']['DATA_TYPE'];
        /**
         * Create table 'company'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::COMPANY_TABLE_NAME)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true],
            'Company ID'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Status'
        )->addColumn(
            'company_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Company Name'
        )->addColumn(
            'legal_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            80,
            ['nullable' => true],
            'Legal Name'
        )->addColumn(
            'company_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Company Email'
        )->addColumn(
            'vat_tax_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'VAT Tax ID'
        )->addColumn(
            'reseller_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Reseller ID'
        )->addColumn(
            'comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Comment'
        )->addColumn(
            'street',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Street'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'City'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2,
            ['nullable' => true],
            'Country ID'
        )->addColumn(
            'region',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Region'
        )->addColumn(
            'region_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Region Id'
        )->addColumn(
            'postcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => true],
            'Postcode'
        )->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => true],
            'Telephone'
        )->addColumn(
            'customer_group_id',
            $customerGroupIdType,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Customer Group ID'
        )->addColumn(
            'sales_representative_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Sales Representative ID'
        )->addColumn(
            'super_user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Super User ID'
        )->addColumn(
            'reject_reason',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Reject Reason'
        )->addColumn(
            'rejected_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Rejected At'
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::COMPANY_TABLE_NAME),
                ['company_name', 'legal_name'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['company_name', 'legal_name'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::COMPANY_TABLE_NAME),
                ['country_id']
            ),
            ['country_id']
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::COMPANY_TABLE_NAME),
                ['region_id']
            ),
            ['region_id']
        )->addForeignKey(
            $installer->getFkName(self::COMPANY_TABLE_NAME, 'country_id', 'directory_country', 'country_id'),
            'country_id',
            $installer->getTable('directory_country'),
            'country_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::COMPANY_TABLE_NAME, 'region_id', 'directory_country_region', 'region_id'),
            'region_id',
            $installer->getTable('directory_country_region'),
            'region_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::COMPANY_TABLE_NAME, 'customer_group_id', 'customer_group', 'customer_group_id'),
            'customer_group_id',
            $installer->getTable('customer_group'),
            'customer_group_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(self::COMPANY_TABLE_NAME, 'sales_representative_id', 'admin_user', 'user_id'),
            'sales_representative_id',
            $installer->getTable('admin_user'),
            'user_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->setComment(
            'Company Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_team'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::TEAM_TABLE_NAME)
        )->addColumn(
            'team_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Company ID'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Description'
        )->setComment(
            'Team Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_structure'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::STRUCTURE_TABLE_NAME)
        )->addColumn(
            'structure_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Structure ID'
        )->addColumn(
            'parent_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Parent Structure ID'
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Entity ID'
        )->addColumn(
            'entity_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Entity type'
        )->addColumn(
            'path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Tree Path'
        )->addColumn(
            'position',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Position'
        )->addColumn(
            'level',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => '0'],
            'Tree Level'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::STRUCTURE_TABLE_NAME),
                ['parent_id']
            ),
            ['parent_id']
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::STRUCTURE_TABLE_NAME),
                ['entity_id']
            ),
            ['entity_id']
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::STRUCTURE_TABLE_NAME),
                ['entity_type']
            ),
            ['entity_type']
        )->setComment(
            'Structure Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_advanced_customer_entity'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME)
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Customer ID'
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Company ID'
        )->addColumn(
            'job_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Job Title'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => CompanyCustomerInterface::STATUS_ACTIVE],
            'Status'
        )->addColumn(
            'telephone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Phone Number'
        )->addForeignKey(
            $installer->getFkName(
                self::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME,
                'customer_id',
                'customer_entity',
                'entity_id'
            ),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addIndex(
            $installer->getIdxName(
                self::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME,
                ['customer_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['customer_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME),
                ['status']
            ),
            ['status']
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_roles'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::ROLES_TABLE_NAME)
        )->addColumn(
            'role_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true],
            'Primary Role ID'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Sorting order'
        )->addColumn(
            'role_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Company role name'
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Company ID'
        )->addForeignKey(
            $installer->getFkName(
                self::ROLES_TABLE_NAME,
                'company_id',
                self::COMPANY_TABLE_NAME,
                'entity_id'
            ),
            'company_id',
            $installer->getTable(self::COMPANY_TABLE_NAME),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::ROLES_TABLE_NAME),
                ['company_id']
            ),
            ['company_id']
        )->setComment(
            'Roles Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_user_roles'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::USER_ROLES_TABLE_NAME)
        )->addColumn(
            'user_role_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Primary User Role ID'
        )->addColumn(
            'role_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Role ID'
        )->addColumn(
            'user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'User ID'
        )->addForeignKey(
            $installer->getFkName(
                self::USER_ROLES_TABLE_NAME,
                'role_id',
                self::ROLES_TABLE_NAME,
                'role_id'
            ),
            'role_id',
            $installer->getTable(self::ROLES_TABLE_NAME),
            'role_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName(
                self::USER_ROLES_TABLE_NAME,
                'user_id',
                'customer_entity',
                'entity_id'
            ),
            'user_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::USER_ROLES_TABLE_NAME),
                ['role_id']
            ),
            ['role_id']
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::USER_ROLES_TABLE_NAME),
                ['user_id']
            ),
            ['user_id']
        )->setComment(
            'User Roles Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_permissions'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::PERMISSIONS_TABLE_NAME)
        )->addColumn(
            'permission_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Primary Permission ID'
        )->addColumn(
            'role_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Role ID'
        )->addColumn(
            'resource_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            80,
            ['nullable' => true],
            'Resource ID'
        )->addColumn(
            'permission',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            80,
            ['nullable' => true],
            'Permission'
        )->addForeignKey(
            $installer->getFkName(
                self::PERMISSIONS_TABLE_NAME,
                'role_id',
                self::ROLES_TABLE_NAME,
                'role_id'
            ),
            'role_id',
            $installer->getTable(self::ROLES_TABLE_NAME),
            'role_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::USER_ROLES_TABLE_NAME),
                ['role_id']
            ),
            ['role_id']
        )->setComment(
            'Permissions Table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'company_order_entity'.
         */
        $table = $installer->getConnection($this->salesConnectionName)->newTable(
            $installer->getTable(self::ORDER_ENTITY_TABLE_NAME)
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity ID'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Order ID'
        )->addColumn(
            'company_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Company ID'
        )->addColumn(
            'company_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true],
            'Company Name'
        )->addForeignKey(
            $installer->getFkName(
                self::ORDER_ENTITY_TABLE_NAME,
                'order_id',
                'sales_order',
                'entity_id'
            ),
            'order_id',
            $installer->getTable('sales_order'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addIndex(
            $installer->getIdxName(
                self::ORDER_ENTITY_TABLE_NAME,
                ['entity_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['entity_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $installer->getIdxName(
                self::ORDER_ENTITY_TABLE_NAME,
                ['order_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['order_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $installer->getConnection($this->salesConnectionName)->createTable($table);

        $installer->endSetup();
    }
}
