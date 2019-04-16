<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Test\Unit\Fixtures;

/**
 * Unit test for SharedCatalogs fixture.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogsFixtureTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Sql\ColumnValueExpressionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $expressionFactory;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPool;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory
     *      |\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogCollectionFactory;

    /**
     * @var \Magento\Setup\Fixtures\FixtureModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fixtureModel;

    /**
     * @var \Magento\Setup\Fixtures\SharedCatalogsFixture
     */
    private $sharedCatalogsFixture;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->categoryCollectionFactory = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->resourceConnection = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()->getMock();
        $this->expressionFactory = $this
            ->getMockBuilder(\Magento\Framework\DB\Sql\ColumnValueExpressionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->metadataPool = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogCollectionFactory = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->fixtureModel = $this->getMockBuilder(\Magento\Setup\Fixtures\FixtureModel::class)
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->sharedCatalogsFixture = $objectManagerHelper->getObject(
            \Magento\Setup\Fixtures\SharedCatalogsFixture::class,
            [
                'fixtureModel' => $this->fixtureModel,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'categoryCollectionFactory' => $this->categoryCollectionFactory,
                'resourceConnection' => $this->resourceConnection,
                'expressionFactory' => $this->expressionFactory,
                'metadataPool' => $this->metadataPool,
                'sharedCatalogCollectionFactory' => $this->sharedCatalogCollectionFactory,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $sharedCatalogCollection = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogCollectionFactory
            ->expects($this->once())->method('create')->willReturn($sharedCatalogCollection);
        $this->fixtureModel->expects($this->atLeastOnce())->method('getValue')
            ->withConsecutive(['shared_catalogs', 0])
            ->willReturnOnConsecutiveCalls(1);
        $sharedCatalogCollection->expects($this->once())->method('getSize')->willReturn(1);
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->setMethods(['select', 'fetchCol', 'fetchOne', 'insert', 'insertFromSelect', 'lastInsertId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->resourceConnection->expects($this->once())->method('getConnection')->willReturn($connection);
        $scSelect = $this->prepareSharedCatalogSelectMock();
        $scpiSelect = $this->prepareProductItemSelectMock();
        list($pSelect, $cSelect) = $this->prepareProductsSelectMock();
        $connection->expects($this->atLeastOnce())->method('insertFromSelect')
            ->with(
                $cSelect,
                \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME,
                ['sku', 'customer_group_id']
            )->willReturn('insert category permissions');
        $aSelect = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()->getMock();
        $aSelect->expects($this->once())->method('from')->with('admin_user')->willReturnSelf();
        $aSelect->expects($this->once())->method('columns')->with('user_id')->willReturnSelf();
        $aSelect->expects($this->once())->method('limit')->with(1)->willReturnSelf();
        $connection->expects($this->atLeastOnce())->method('insert')
            ->withConsecutive(
                ['customer_group'],
                [\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME]
            )->willReturn(1);
        $connection->expects($this->atLeastOnce())->method('lastInsertId')
            ->withConsecutive(
                ['customer_group'],
                [\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME]
            )->willReturnOnConsecutiveCalls(7, 8);
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with(8)->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->wilLReturn(7);
        $metadata = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityMetadataInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')->with(\Magento\Catalog\Api\Data\ProductInterface::class)->willReturn($metadata);
        $metadata->expects($this->once())->method('getLinkField')->willReturn('row_id');
        $pricesSelect = $this->prepareProductPricesSelectMock();
        $cgSelect = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()->getMock();
        $cgSelect->expects($this->once())->method('from')
            ->with(
                ['sc' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME],
                ['customer_group_id']
            )->willReturnSelf();
        $cgSelect->expects($this->once())->method('distinct')->with(true)->willReturnSelf();
        $cpSelect = $this->prepareCategoryPermissionsSelectMock();
        $cpiSelect = $this->prepareCategoryPermissionsIndexSelectMock();
        $this->prepareResourceConnectionMock();
        $this->prepareExpressionFactoryMock();
        $connection->expects($this->atLeastOnce())->method('fetchCol')
            ->withConsecutive([$scSelect], [$cSelect], [$cgSelect])
            ->willReturnOnConsecutiveCalls([1], [3, 4, 5], [1, 7]);
        $connection->expects($this->atLeastOnce())->method('fetchOne')
            ->withConsecutive($scpiSelect, $aSelect)->willReturn(0, 6);
        $connection->expects($this->atLeastOnce())->method('select')
            ->willReturnOnConsecutiveCalls(
                $scSelect,
                $scpiSelect,
                $pSelect,
                $pSelect,
                $aSelect,
                $pSelect,
                $pricesSelect,
                $cgSelect,
                $cpSelect,
                $cpSelect,
                $cpSelect,
                $cpiSelect
            );
        $dbStatement = $this->getMockBuilder(\Zend_Db_Statement_Interface::class)
            ->disableOriginalConstructor()->getMock();
        $connection->expects($this->atLeastOnce())->method('query')
            ->withConsecutive(
                ['insert category permissions'],
                ['insert category permissions'],
                ['insert category permissions'],
                ['insert product prices'],
                ['insert allow category permissions'],
                ['insert allow category permissions'],
                ['insert allow category permissions'],
                ['insert category permissions index']
            )->willReturn($dbStatement);
        $this->sharedCatalogsFixture->execute();
    }

    /**
     * Prepare select mock for shared_catalog table.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareSharedCatalogSelectMock()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('from')
            ->with(['sc' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME], [])
            ->willReturnSelf();
        $select->expects($this->once())->method('joinLeft')
            ->with(
                ['scpi' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME],
                'scpi.customer_group_id = sc.customer_group_id',
                []
            )
            ->willReturnSelf();
        $select->expects($this->once())->method('columns')->with(['customer_group_id'])->willReturnSelf();
        $select->expects($this->once())->method('where')->with('scpi.entity_id IS NULL')->willReturnSelf();

        return $select;
    }

    /**
     * Prepare select mock for shared_catalog_product_item table.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareProductItemSelectMock()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('from')
            ->with(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME)
            ->willReturnSelf();
        $select->expects($this->once())->method('columns')->with(['entity_id'])->willReturnSelf();
        $select->expects($this->once())->method('where')->with('customer_group_id = 0')->willReturnSelf();
        $select->expects($this->atLeastOnce())->method('limit')->with(1)->willReturnSelf();

        return $select;
    }

    /**
     * Prepare select mock for category permissions.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareCategoryPermissionsSelectMock()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $select->expects($this->atLeastOnce())->method('from')
            ->with(['category' => 'catalog_category_entity'], [])->willReturnSelf();
        $select->expects($this->atLeastOnce())->method('distinct')->willReturnSelf();
        $select->expects($this->atLeastOnce())->method('columns')
            ->withConsecutive(
                [
                    [
                        'permission' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                        'website_id' => 'NULL',
                        'category_id' => 'category.entity_id',
                        'customer_group_id' => 0,
                    ],
                ],
                [
                    [
                        'permission' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                        'website_id' => 'NULL',
                        'category_id' => 'category.entity_id',
                        'customer_group_id' => 1,
                    ],
                ],
                [
                    [
                        'permission' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                        'website_id' => 'NULL',
                        'category_id' => 'category.entity_id',
                        'customer_group_id' => 7,
                    ],
                ]
            )->willReturnSelf();
        $select->expects($this->atLeastOnce())->method('joinLeft')
            ->withConsecutive(
                [
                    ['perm' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME],
                    'perm.category_id = category.entity_id AND perm.customer_group_id = 0',
                    []
                ],
                [
                    ['perm' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME],
                    'perm.category_id = category.entity_id AND perm.customer_group_id = 1',
                    []
                ],
                [
                    ['perm' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME],
                    'perm.category_id = category.entity_id AND perm.customer_group_id = 7',
                    []
                ]
            )->willReturnSelf();
        $select->expects($this->atLeastOnce())
            ->method('where')
            ->with('perm.category_id IS NULL')
            ->willReturnSelf();
        $select->expects($this->atLeastOnce())->method('insertFromSelect')
            ->with(
                \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME,
                ['permission', 'website_id', 'category_id', 'customer_group_id']
            )->willReturn('insert allow category permissions');

        return $select;
    }

    /**
     * Prepare select mocks for products and categories.
     *
     * @return array
     */
    private function prepareProductsSelectMock()
    {
        $pSelect = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $pSelect->expects($this->atLeastOnce())->method('distinct')->with(true)->willReturnSelf();
        $pSelect->expects($this->atLeastOnce())->method('from')
            ->with(['product' => 'catalog_product_entity'], [])->willReturnSelf();
        $pSelect->expects($this->atLeastOnce())->method('columns')
            ->withConsecutive([['sku', 'customer_group_id' => 1]], [['sku', 'customer_group_id' => 0]])
            ->willReturnSelf();
        $pSelect->expects($this->atLeastOnce())->method('joinLeft')
            ->withConsecutive(
                [
                    ['category' => 'catalog_category_product'],
                    'category.product_id = product.entity_id',
                    []
                ],
                [
                    ['product_link' => 'catalog_product_super_link'],
                    'product_link.product_id = product.entity_id',
                    []
                ]
            )
            ->willReturnSelf();
        $categoryCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->categoryCollectionFactory->expects($this->once())->method('create')->willReturn($categoryCollection);
        $categoryCollection->expects($this->once())
            ->method('addFieldToFilter')->with('level', ['gt' => 1])->willReturnSelf();
        $cSelect = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $categoryCollection->expects($this->atLeastOnce())->method('getSelect')->willReturn($cSelect);
        $categoryCollection->expects($this->once())->method('getIdFieldName')->willReturn('row_id');
        $cSelect->expects($this->once())->method('columns')->with(['row_id'])->willReturnSelf();
        $pSelect->expects($this->atLeastOnce())->method('where')
            ->with('category.category_id in (?)', [3, 4])->willReturnSelf();
        $pSelect->expects($this->atLeastOnce())->method('orWhere')
            ->with('product_link.parent_id IS NOT NULL AND product_link.parent_id != 0')->willReturnSelf();

        return [$pSelect, $cSelect];
    }

    /**
     * Prepare select mock for product prices.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareProductPricesSelectMock()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('from')
            ->with(
                ['product_item' => 'shared_catalog_product_item'],
                [],
                null
            )->willReturnSelf();
        $select->expects($this->once())->method('columns')
            ->willReturnSelf();
        $select->expects($this->once())->method('join')
            ->with(
                ['product' => 'catalog_product_entity'],
                'product_item.sku = product.sku',
                []
            )
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('where')->with('product_item.customer_group_id = ?', 7)->willReturnSelf();
        $select->expects($this->once())->method('insertFromSelect')
            ->with(
                'catalog_product_entity_tier_price',
                ['row_id', 'all_groups', 'customer_group_id', 'qty', 'percentage_value', 'website_id']
            )->willReturn('insert product prices');

        return $select;
    }

    /**
     * Prepare select mock for catalog permissions index.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareCategoryPermissionsIndexSelectMock()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('from')
            ->with(
                ['shared_catalog' => \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME],
                []
            )->willReturnSelf();
        $select->expects($this->once())->method('columns')
            ->with(
                [
                    'category_id' => 'shared_catalog.category_id',
                    'website_id' => 'shared_catalog.website_id',
                    'customer_group_id' => 'shared_catalog.customer_group_id',
                    'grant_catalog_category_view' => 'shared_catalog.permission',
                    'grant_catalog_product_price' => 'shared_catalog.permission',
                    'grant_checkout_items' => 'shared_catalog.permission',
                ]
            )->willReturnSelf();
        $select->expects($this->once())->method('insertFromSelect')
            ->with(
                'magento_catalogpermissions',
                [
                    'category_id',
                    'website_id',
                    'customer_group_id',
                    'grant_catalog_category_view',
                    'grant_catalog_product_price',
                    'grant_checkout_items'
                ]
            )->willReturn('insert category permissions index');

        return $select;
    }

    /**
     * Prepare mock of resource connection.
     *
     * @return void
     */
    private function prepareResourceConnectionMock()
    {
        $this->resourceConnection->expects($this->atLeastOnce())->method('getTableName')
            ->withConsecutive(
                [\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME],
                [\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME],
                [\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME],
                ['catalog_product_entity'],
                ['catalog_category_product'],
                ['catalog_product_super_link'],
                ['magento_catalogpermissions'],
                ['admin_user'],
                ['customer_group'],
                ['catalog_product_entity_tier_price'],
                ['catalog_category_entity'],
                ['magento_catalogpermissions']
            )
            ->willReturnArgument(0);
    }

    /**
     * Prepare mock of expression factory.
     *
     * @return void
     */
    private function prepareExpressionFactoryMock()
    {
        $this->expressionFactory->expects($this->atLeastOnce())->method('create')
            ->withConsecutive(
                [['expression' => 1]],
                [['expression' => 0]],
                [['expression' => 7]],
                [['expression' => 0]],
                [['expression' => 1]],
                [['expression' => 'FLOOR(75 + RAND() * 25)']],
                [['expression' => 0]],
                [['expression' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW]],
                [['expression' => 'NULL']],
                [['expression' => 0]],
                [['expression' => 1]],
                [['expression' => 7]]
            )->willReturnOnConsecutiveCalls(
                1,
                0,
                7,
                0,
                1,
                'FLOOR(75 + RAND() * 25)',
                0,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW,
                'NULL',
                0,
                1,
                7
            );
    }
}
