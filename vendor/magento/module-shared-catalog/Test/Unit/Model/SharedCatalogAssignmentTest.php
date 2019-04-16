<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test for \Magento\SharedCatalog\Model\SharedCatalogAssignment class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogAssignmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product;

    /**
     * @var \Magento\SharedCatalog\Api\ProductManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productManagement;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogAssignment
     */
    private $sharedCatalogAssignment;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds'])
            ->getMockForAbstractClass();
        $this->productManagement = $this->getMockBuilder(\Magento\SharedCatalog\Api\ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository = $this->getMockBuilder(
            \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository = $this->getMockBuilder(
            \Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogInvalidation = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\SharedCatalogInvalidation::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogAssignment = $this->objectManagerHelper->getObject(
            \Magento\SharedCatalog\Model\SharedCatalogAssignment::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'productRepository' => $this->productRepository,
                'productManagement' => $this->productManagement,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
            ]
        );
    }

    /**
     * Test assignProductsForCategories method.
     *
     * @return void
     */
    public function testAssignProductsForCategories()
    {
        $sharedCatalogId = 3;
        $assignCategoriesIds = [12, 15];
        $productsSearchResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('category_id', $assignCategoriesIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->productRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($productsSearchResult);
        $productsSearchResult->expects($this->once())->method('getItems')->willReturn([$this->product]);
        $this->productManagement->expects($this->once())
            ->method('assignProducts')
            ->with($sharedCatalogId, [$this->product])
            ->willReturn(true);
        $this->sharedCatalogAssignment->assignProductsForCategories($sharedCatalogId, $assignCategoriesIds);
    }

    /**
     * Test unassignProductsForCategories method.
     *
     * @return void
     */
    public function testUnassignProductsForCategories()
    {
        $sharedCatalogId = 3;
        $assignCategoriesIds = [12, 15];
        $unAssignCategoriesIds = [12, 20];
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds', 'getSku'])
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')
            ->with($sharedCatalogId)
            ->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('customer_group_id', 2)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$productItem]);
        $productItem->expects($this->once())->method('getSku')->willReturn('sku');
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())
            ->method('checkProductExist')
            ->willReturn($productItem);
        $productItem->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn([]);
        $this->productManagement->expects($this->once())
            ->method('unassignProducts')
            ->with($sharedCatalogId, [$productItem])
            ->willReturn(true);
        $this->sharedCatalogAssignment->unassignProductsForCategories(
            $sharedCatalogId,
            $unAssignCategoriesIds,
            $assignCategoriesIds
        );
    }

    /**
     * Test getAssignCategoryIdsByProductSkus method.
     *
     * @return void
     */
    public function testGetAssignCategoryIdsByProductSkus()
    {
        $assignProductsSkus = ['sku_1', 'sku_2'];
        $categoryIds = [9, 13];
        $productsSearchResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('sku', $assignProductsSkus, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->productRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($productsSearchResult);
        $productsSearchResult->expects($this->once())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn($categoryIds);
        $this->assertSame(
            $categoryIds,
            $this->sharedCatalogAssignment->getAssignCategoryIdsByProductSkus($assignProductsSkus)
        );
    }

    /**
     * Test getAssignProductSkusByCategoryIds method.
     *
     * @return void
     */
    public function testGetAssignProductSkusByCategoryIds()
    {
        $assignCategoriesIds = [12, 15];
        $productSku = 'sku_1';
        $productsSearchResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('category_id', $assignCategoriesIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->productRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($productsSearchResult);
        $productsSearchResult->expects($this->once())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->once())->method('getSku')->willReturn($productSku);
        $this->assertSame(
            [$productSku],
            $this->sharedCatalogAssignment->getAssignProductSkusByCategoryIds($assignCategoriesIds)
        );
    }

    /**
     * Test getProductSkusToUnassign method.
     *
     * @return void
     */
    public function testGetProductSkusToUnassign()
    {
        $unassignCategoriesIds = [15, 20];
        $assignedCategoriesIds = [15, 21];
        $productSku = 'sku_1';
        $productsSearchResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('category_id', $unassignCategoriesIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->productRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($productsSearchResult);
        $productsSearchResult->expects($this->once())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn([]);
        $this->product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->assertSame(
            [$productSku],
            $this->sharedCatalogAssignment->getProductSkusToUnassign($unassignCategoriesIds, $assignedCategoriesIds)
        );
    }
}
