<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\QuickOrder\Test\Unit\Model\ResourceModel\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;

/**
 * Unit tests for Suggest resource model.
 */
class SuggestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\QuickOrder\Model\ResourceModel\Product\Suggest
     */
    private $suggest;

    /**
     * @var \Magento\QuickOrder\Model\CatalogPermissions\Permissions|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionsMock;

    /**
     * @var \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tempStorageFactoryMock;

    /**
     * @var \Magento\Framework\DB\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbHelperMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->permissionsMock = $this->getMockBuilder(\Magento\QuickOrder\Model\CatalogPermissions\Permissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tempStorageFactoryMock = $this->getMockBuilder(TemporaryStorageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbHelperMock = $this->getMockBuilder(\Magento\Framework\DB\Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->suggest = $this->objectManagerHelper->getObject(
            \Magento\QuickOrder\Model\ResourceModel\Product\Suggest::class,
            [
                'permissions' => $this->permissionsMock,
                'tempStorageFactory' => $this->tempStorageFactoryMock,
                'dbHelper' => $this->dbHelperMock
            ]
        );
    }

    /**
     * Test for prepareProductCollection().
     *
     * @return void
     */
    public function testPrepareProductCollection()
    {
        $tableName = 'table_name';
        $query = 'test';

        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods([
                'addAttributeToSelect',
                'getSelect',
                'setOrder',
                'addAttributeToFilter'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->expects($this->once())->method('addAttributeToSelect')->willReturnSelf();
        $tempStorageMock = $this->getMockBuilder(\Magento\Framework\Search\Adapter\Mysql\TemporaryStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tempStorageFactoryMock->expects($this->once())->method('create')->willReturn($tempStorageMock);
        $tableMock = $this->getMockBuilder(\Magento\Framework\DB\Ddl\Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fulltextSearchResultsMock = $this->getMockBuilder(\Magento\Framework\Api\Search\DocumentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fulltextSearchResults = [$fulltextSearchResultsMock];
        $tempStorageMock->expects($this->once())->method('storeApiDocuments')
            ->with($fulltextSearchResults)
            ->willReturn($tableMock);
        $tableMock->expects($this->once())->method('getName')->willReturn($tableName);
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->expects($this->atLeastOnce())->method('getSelect')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('joinInner')
            ->with(
                [
                    'search_result' => $tableName,
                ],
                'e.entity_id = search_result.' . TemporaryStorage::FIELD_ENTITY_ID,
                []
            )
            ->willReturnSelf();
        $this->permissionsMock->expects($this->once())->method('applyPermissionsToProductCollection')
            ->with($productCollectionMock)
            ->willReturnSelf();
        $selectMock->expects($this->once())->method('order')->with('search_result.score DESC')->willReturnSelf();
        $this->dbHelperMock->expects($this->once())->method('escapeLikeValue')
            ->with($query, ['position' => 'any'])->willReturn($query);
        $productCollectionMock->expects($this->exactly(2))->method('addAttributeToFilter')
            ->withConsecutive(
                [
                    [
                        ['attribute' => \Magento\Catalog\Api\Data\ProductInterface::SKU, 'like' => $query],
                        ['attribute' => \Magento\Catalog\Api\Data\ProductInterface::NAME, 'like' => $query]
                    ]
                ],
                [
                    [
                        ['attribute' => 'required_options', 'neq' => 1],
                        [
                            'attribute' => \Magento\Catalog\Api\Data\ProductInterface::VISIBILITY,
                            'in' => [
                                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
                            ]
                        ]
                    ]
                ]
            )
            ->willReturnSelf();

        $this->suggest->prepareProductCollection($productCollectionMock, $fulltextSearchResults, 10, $query);
    }
}
