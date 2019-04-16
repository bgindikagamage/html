<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Fixtures;

use \Magento\SharedCatalog\Setup\InstallSchema as SharedCatalogInstallSchema;

/**
 * Generates Shared Catalogs fixtures.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogsFixture extends Fixture
{
    /**
     * Unregistered user role id.
     *
     * @var int
     */
    const SHARED_CATALOG_UNREGISTERED_USER_ROLE_ID = 0;

    /**
     * Default tax class id
     *
     * @var int
     */
    const DEFAULT_TAX_CLASS_ID = 3;

    /**
     * @var int
     */
    protected $priority = 150;

    /**
     * Percentage value of all categories included to shared catalog.
     *
     * @var int
     */
    private $categoriesPercentInSharedCatalog = 75;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $dbConnection;

    /**
     * @var \Magento\Framework\DB\Sql\ColumnValueExpressionFactory
     */
    private $expressionFactory;

    /**
     * @var array|null
     */
    private $categoriesIds;

    /**
     * @var array
     */
    private $tableCache = [];

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory
     */
    private $sharedCatalogCollectionFactory;

    /**
     * @param FixtureModel $fixtureModel
     * @param \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\DB\Sql\ColumnValueExpressionFactory $expressionFactory
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory $sharedCatalogCollectionFactory
     */
    public function __construct(
        FixtureModel $fixtureModel,
        \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\DB\Sql\ColumnValueExpressionFactory $expressionFactory,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory $sharedCatalogCollectionFactory
    ) {
        parent::__construct($fixtureModel);

        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->expressionFactory = $expressionFactory;
        $this->metadataPool = $metadataPool;
        $this->sharedCatalogCollectionFactory = $sharedCatalogCollectionFactory;
    }

    /**
     * Generate shared catalogs and assign products to them.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->updateExistsSharedCatalog();

        $sharedCatalogsCount = $this->getSharedCatalogsAmount();
        if (!$sharedCatalogsCount) {
            return;
        }
        $customerGroupId = [];
        $adminUserId = $this->getDbConnection()->fetchOne(
            $this->getDbConnection()->select()
                ->from($this->getTable('admin_user'))
                ->columns('user_id')
                ->limit(1)
        );

        for ($sharedCatalogIndex = 1; $sharedCatalogIndex <= $sharedCatalogsCount; $sharedCatalogIndex++) {
            $sharedCatalog = $this->createSharedCatalog($sharedCatalogIndex, $adminUserId);
            $this->assignProductsToSharedCatalog($sharedCatalog->getCustomerGroupId());
            $customerGroupId[] = $sharedCatalog->getCustomerGroupId();
        }
        $this->setSharedCatalogPrices($customerGroupId);
        $this->assignPermissionsToCategories();
        $this->assignCategoriesPermissionsToIndex();
    }

    /**
     * Assign default permissions to shared catalogs.
     *
     * @return void
     */
    private function updateExistsSharedCatalog()
    {
        $scTable = $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_TABLE_NAME);
        $scProductItemTable = $this->getTable(
            SharedCatalogInstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME
        );
        $scPermissionsTable = $this->getTable(
            SharedCatalogInstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME
        );

        $select = $this->getDbConnection()->select()
            ->from(['sc' => $scTable], [])
            ->joinLeft(
                ['scpi' => $scProductItemTable],
                'scpi.customer_group_id = sc.customer_group_id',
                []
            )
            ->columns(['customer_group_id'])
            ->where('scpi.entity_id IS NULL');
        $customerGroupIds = $this->getDbConnection()->fetchCol($select);
        $select = $this->getDbConnection()->select()
            ->from($scProductItemTable)
            ->columns(['entity_id'])
            ->where('customer_group_id = 0')
            ->limit(1);
        if ($this->getDbConnection()->fetchOne($select) == 0) {
            $customerGroupIds[] = '0';
        }

        foreach ($customerGroupIds as $customerGroupId) {
            $this->assignProductsToSharedCatalog($customerGroupId);
        }
        $this->getDbConnection()->update(
            $scPermissionsTable,
            ['permission' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW],
            'permission = ' . \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
        );
        $this->getDbConnection()->update(
            $scTable = $this->getTable('magento_catalogpermissions'),
            [
                'grant_catalog_category_view' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                'grant_catalog_product_price' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                'grant_checkout_items' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
            ],
            'grant_catalog_category_view = ' . \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
        );
    }

    /**
     * Get amount of shared catalog to be generated.
     *
     * @return int
     */
    private function getSharedCatalogsAmount()
    {
        $sharedCatalogCollection = $this->sharedCatalogCollectionFactory->create();

        // minus default shared catalog
        return max(
            0,
            (int)$this->fixtureModel->getValue('shared_catalogs', 0) - ($sharedCatalogCollection->getSize() - 1)
        );
    }

    /**
     * Create shared catalog.
     *
     * Uses $index to generate unique name and description.
     *
     * @param int $index
     * @param int $adminUserId
     * @return \Magento\SharedCatalog\Api\Data\SharedCatalogInterface
     */
    private function createSharedCatalog($index, $adminUserId)
    {
        $this->getDbConnection()->insert(
            $this->getTable('customer_group'),
            [
                'customer_group_code' => 'Shared catalog ' . $index . ' ' . uniqid(),
                'tax_class_id' => self::DEFAULT_TAX_CLASS_ID,
            ]
        );
        $customerGroupId = $this->getDbConnection()->lastInsertId($this->getTable('customer_group'));

        $this->getDbConnection()->insert(
            $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_TABLE_NAME),
            [
                'name' => 'Shared catalog ' . $index . ' ' . uniqid(),
                'description' => 'Shared catalog description ' . $index,
                'type' => \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_CUSTOM,
                'created_by' => $adminUserId,
                'created_at' => date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
                'customer_group_id' => $customerGroupId
            ]
        );

        $sharedCatalogId = $this->getDbConnection()
            ->lastInsertId(
                $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_TABLE_NAME)
            );

        return $this->sharedCatalogRepository->get($sharedCatalogId);
    }

    /**
     * Assign products to shared catalog.
     *
     * Creates INSERT ... FROM SELECT query
     * where insert is made to table that links shared catalogs and products
     * and select retrieves current customer group id from shared catalog
     * and all product sku assigned to list of categories.
     *
     * @param int $customerGroupId
     * @return void
     */
    private function assignProductsToSharedCatalog($customerGroupId)
    {
        $select = $this->getDbConnection()
            ->select()
            ->distinct(true)
            ->from(['product' => $this->getTable('catalog_product_entity')], [])
            ->columns(
                ['sku', 'customer_group_id' => $this->expressionFactory->create(['expression' => $customerGroupId])]
            )->joinLeft(
                ['category' => $this->getTable('catalog_category_product')],
                'category.product_id = product.entity_id',
                []
            )->joinLeft(
                ['product_link' => $this->getTable('catalog_product_super_link')],
                'product_link.product_id = product.entity_id',
                []
            )
            ->where('category.category_id in (?)', $this->getCategoriesIds())
            ->orWhere('product_link.parent_id IS NOT NULL AND product_link.parent_id != 0');

        $insert = $this->getDbConnection()
            ->insertFromSelect(
                $select,
                $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME),
                ['sku', 'customer_group_id']
            );

        $this->getDbConnection()->query($insert);
    }

    /**
     * Retrieve current connection to DB.
     *
     * Method is required to eliminate multiple calls to ResourceConnection class.
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getDbConnection()
    {
        if ($this->dbConnection === null) {
            $this->dbConnection = $this->resourceConnection->getConnection();
        }

        return $this->dbConnection;
    }

    /**
     * Retrieve real table name.
     *
     * Method act like a cache for already retrieved table names
     * is required to eliminate multiple calls to ResourceConnection class.
     *
     * @param string $tableName
     * @return string
     */
    private function getTable($tableName)
    {
        if (!isset($this->tableCache[$tableName])) {
            $this->tableCache[$tableName] = $this->resourceConnection->getTableName($tableName);
        }

        return $this->tableCache[$tableName];
    }

    /**
     * Retrieves categories IDs.
     *
     * Retrieves ids for all categories except Root and Default
     * and limit its number to $categoriesPercentInSharedCatalog.
     *
     * @return array
     */
    private function getCategoriesIds()
    {
        if ($this->categoriesIds === null) {
            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('level', ['gt' => 1])
                ->getSelect()
                ->columns([$categoryCollection->getIdFieldName()]);

            $this->categoriesIds = $this->getDbConnection()->fetchCol($categoryCollection->getSelect());
            $this->categoriesIds = $this->limitCategoriesIds($this->categoriesIds);
        }

        return $this->categoriesIds;
    }

    /**
     * Cut array of categories according to $categoriesPercentInSharedCatalog.
     *
     * @param array $fullCategoriesList
     * @return array
     */
    private function limitCategoriesIds(array $fullCategoriesList)
    {
        $necessaryCount = round(count($fullCategoriesList) * $this->categoriesPercentInSharedCatalog / 100);

        return array_slice($fullCategoriesList, 0, $necessaryCount);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionTitle()
    {
        return 'Generating shared catalogs';
    }

    /**
     * {@inheritdoc}
     */
    public function introduceParamLabels()
    {
        return ['shared_catalogs' => 'Shared Catalogs'];
    }

    /**
     * Set random price for shared catalog items. Set random discount in 75-100 % from product base price.
     *
     * @param array $customerGroupId
     * @return void
     */
    private function setSharedCatalogPrices(array $customerGroupId)
    {
        $connection = $this->getDbConnection();
        $linkFieldId = $this->metadataPool->getMetadata(
            \Magento\Catalog\Api\Data\ProductInterface::class
        )->getLinkField();
        foreach ($customerGroupId as $customerGroup) {
            $columns = [
                $linkFieldId => 'product.' . $linkFieldId,
                'all_groups' => $this->expressionFactory->create([
                    'expression' => 0
                ]),
                'customer_group_id' => 'product_item.customer_group_id',
                'qty' => $this->expressionFactory->create([
                    'expression' => 1
                ]),
                'percentage_value' => $this->expressionFactory->create([
                    'expression' => 'FLOOR(75 + RAND() * 25)'
                ]),
                'website_id' => $this->expressionFactory->create([
                    'expression' => 0
                ]),
            ];
            $select = $connection->select()
                ->from(
                    [
                        'product_item' => $this->getTable(
                            SharedCatalogInstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME
                        )
                    ],
                    []
                )
                ->columns($columns)
                ->join(
                    ['product' => $this->getTable('catalog_product_entity')],
                    'product_item.sku = product.sku',
                    []
                )->where('product_item.customer_group_id = ?', $customerGroup);

            $connection->query(
                $select->insertFromSelect(
                    $this->getTable('catalog_product_entity_tier_price'),
                    array_keys($columns)
                )
            );
        }
    }

    /**
     * Assign permissions to categories of shared catalog.
     *
     * @return void
     */
    private function assignPermissionsToCategories()
    {
        $select = $this->getDbConnection()
            ->select()
            ->distinct(true)
            ->from(
                ['sc' => $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_TABLE_NAME)],
                ['customer_group_id']
            );

        $customerGroupIds = $this->getDbConnection()->fetchCol($select);
        array_unshift($customerGroupIds, self::SHARED_CATALOG_UNREGISTERED_USER_ROLE_ID);

        $connection = $this->getDbConnection();
        $columns = [
            'permission' => $this->expressionFactory->create([
                'expression' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
            ]),
            'website_id' => $this->expressionFactory->create([
                'expression' => 'NULL'
            ]),
            'category_id' => 'category.entity_id',
        ];
        foreach ($customerGroupIds as $customerGroupId) {
            $columns['customer_group_id'] = $this->expressionFactory->create(['expression' => $customerGroupId]);
            $select = $connection->select()
                ->from(['category' => $this->getTable('catalog_category_entity')], [])
                ->distinct()
                ->columns($columns)
                ->joinLeft(
                    ['perm' => $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME)],
                    'perm.category_id = category.entity_id AND perm.customer_group_id = '.$customerGroupId,
                    []
                )
                ->where('perm.category_id IS NULL');

            $connection->query(
                $select->insertFromSelect(
                    $this->getTable(SharedCatalogInstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME),
                    array_keys($columns)
                )
            );
        }
    }

    /**
     * Assign permissions to category permissions table.
     *
     * @return void
     */
    private function assignCategoriesPermissionsToIndex()
    {
        $connection = $this->getDbConnection();
        $columns = [
            'category_id' => 'shared_catalog.category_id',
            'website_id' => 'shared_catalog.website_id',
            'customer_group_id' => 'shared_catalog.customer_group_id',
            'grant_catalog_category_view' => 'shared_catalog.permission',
            'grant_catalog_product_price' => 'shared_catalog.permission',
            'grant_checkout_items' => 'shared_catalog.permission',
        ];
        $select = $connection->select()
            ->from(
                [
                    'shared_catalog' => $this->getTable(
                        SharedCatalogInstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME
                    )
                ],
                []
            )
            ->columns($columns);

        $connection->query(
            $select->insertFromSelect(
                $this->getTable('magento_catalogpermissions'),
                array_keys($columns)
            )
        );
    }
}
