<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

/**
 * CategoryManagement unit test.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogAssignment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogAssignment;

    /**
     * @var \Magento\CatalogPermissions\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionsHelper;

    /**
     * @var \Magento\SharedCatalog\Model\CategoryManagement
     */
    private $categoryManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->categoryRepository = $this->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->sharedCatalogInvalidation = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogInvalidation::class)
            ->disableOriginalConstructor()->getMock();
        $this->catalogPermissionManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\CatalogPermissionManagement::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->sharedCatalogAssignment = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogAssignment::class)
            ->disableOriginalConstructor()->getMock();
        $this->permissionsHelper = $this
            ->getMockBuilder(\Magento\CatalogPermissions\Helper\Data::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->categoryManagement = $objectManager->getObject(
            \Magento\SharedCatalog\Model\CategoryManagement::class,
            [
                'categoryRepository' => $this->categoryRepository,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
                'storeManager' => $this->storeManager,
                'sharedCatalogAssignment' => $this->sharedCatalogAssignment,
                'permissionsHelper' => $this->permissionsHelper,
            ]
        );
    }

    /**
     * Test for getCategories method.
     *
     * @param int|null $storeId
     * @param int $rootCategoryId
     * @param int $rootCategoryIdCalls
     * @param array $expectedResult
     * @return void
     * @dataProvider getCategoriesDataProvider
     */
    public function testGetCategories(
        $storeId,
        $rootCategoryId,
        $rootCategoryIdCalls,
        array $expectedResult
    ) {
        $sharedCatalogId = 1;
        $websiteId = 7;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('checkSharedCatalogExist')->with($sharedCatalogId)->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())
            ->method('getGroup')->with((int)$storeId)->willReturn($store);
        $store->expects($this->exactly($rootCategoryIdCalls))
            ->method('getRootCategoryId')->willReturn($rootCategoryId);
        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())->method('get')->with($rootCategoryId)->willReturn($category);
        $category->expects($this->once())->method('getAllChildren')->with(true)->willReturn([4, 5, 6]);
        $store->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->catalogPermissionManagement->expects($this->once())->method('getAllowedCategoriesIds')
            ->with($sharedCatalogId, $websiteId)->willReturn([6]);

        $this->assertEquals($expectedResult, $this->categoryManagement->getCategories($sharedCatalogId));
    }

    /**
     * Test for assignCategories method.
     *
     * @return void
     */
    public function testAssignCategories()
    {
        $sharedCatalogId = 1;
        $categoryId = 2;
        $customerGroupId = 5;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('checkSharedCatalogExist')->with($sharedCatalogId)->willReturn($sharedCatalog);

        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $category->expects($this->once())->method('getId')->willReturn($categoryId);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())
            ->method('getGroup')->with(\Magento\Store\Model\Store::DEFAULT_STORE_ID)->willReturn($store);
        $rootCategory = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())->method('get')->with(1)->willReturn($rootCategory);
        $rootCategory->expects($this->once())->method('getAllChildren')->with(true)->willReturn([2, 3, 4]);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setAllowPermissions')->with([$categoryId], [$customerGroupId]);
        $this->assertTrue($this->categoryManagement->assignCategories($sharedCatalogId, [$category]));
    }

    /**
     * Test for assignCategories method with exception.
     *
     * @return void
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Requested categories don't exist: 2
     */
    public function testAssignCategoriesWithException()
    {
        $sharedCatalogId = 1;
        $categoryId = 2;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('checkSharedCatalogExist')->with($sharedCatalogId)->willReturn($sharedCatalog);

        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $category->expects($this->once())->method('getId')->willReturn($categoryId);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())
            ->method('getGroup')->with(\Magento\Store\Model\Store::DEFAULT_STORE_ID)->willReturn($store);
        $rootCategory = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())->method('get')->with(1)->willReturn($rootCategory);
        $rootCategory->expects($this->once())->method('getAllChildren')->with(true)->willReturn([3, 4]);
        $this->categoryManagement->assignCategories($sharedCatalogId, [$category]);
    }

    /**
     * Test for unassignCategories method.
     *
     * @return void
     */
    public function testUnassignCategories()
    {
        $sharedCatalogId = 1;
        $categoryId = 2;
        $customerGroupId = 5;
        $storeId = 6;
        $rootCategoryId = 7;
        $websiteId = 8;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())
            ->method('checkSharedCatalogExist')->with($sharedCatalogId)->willReturn($sharedCatalog);

        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $category->expects($this->once())->method('getId')->willReturn($categoryId);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')
            ->withConsecutive([\Magento\Store\Model\Store::DEFAULT_STORE_ID], [$storeId])
            ->willReturn($store);
        $rootCategory = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->atLeastOnce())
            ->method('get')->withConsecutive([1], [$rootCategoryId])->willReturn($rootCategory);
        $rootCategory->expects($this->atLeastOnce())->method('getAllChildren')->with(true)->willReturn([2, 3, 4]);
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->once())
            ->method('getType')
            ->willReturn(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setDenyPermissions')
            ->with([$categoryId], [$customerGroupId, \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID]);
        $this->sharedCatalogAssignment->expects($this->once())
            ->method('unassignProductsForCategories')->with($sharedCatalogId, [$categoryId]);
        $sharedCatalog->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $store->expects($this->once())->method('getRootCategoryId')->willReturn($rootCategoryId);
        $store->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->catalogPermissionManagement->expects($this->once())->method('getAllowedCategoriesIds')
            ->with($sharedCatalogId, $websiteId)->willReturn([3]);
        $this->assertTrue($this->categoryManagement->unassignCategories($sharedCatalogId, [$category]));
    }

    /**
     * Data provider for testGetCategories.
     *
     * @return array
     */
    public function getCategoriesDataProvider()
    {
        return [
            [2, 3, 1, [6]],
            [null, 1, 0, [6]],
        ];
    }
}
