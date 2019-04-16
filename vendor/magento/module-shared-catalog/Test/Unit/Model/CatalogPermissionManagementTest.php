<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as SharedCatalogPermissionFactory;
use Magento\SharedCatalog\Api\Data\PermissionInterface;

/**
 * Unit test for Magento\SharedCatalog\Model\CatalogPermissionManagement class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogPermissionManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogScheduler;

    /**
     * @var SharedCatalogPermissionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogPermissionCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionResource;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->permissionCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepository = $this->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogInvalidation = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogInvalidation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogScheduler = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogBulkPublisher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->customerGroupManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permissionResource = $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->catalogPermissionManagement = $objectManagerHelper->getObject(
            \Magento\SharedCatalog\Model\CatalogPermissionManagement::class,
            [
                'permissionCollectionFactory' => $this->permissionCollectionFactory,
                'categoryRepository' => $this->categoryRepository,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
                'sharedCatalogScheduler' => $this->sharedCatalogScheduler,
                'sharedCatalogPermissionCollectionFactory' => $this->sharedCatalogPermissionCollectionFactory,
                'storeManager' => $this->storeManager,
                'customerGroupManagement' => $this->customerGroupManagement,
                'permissionResource' => $this->permissionResource,
            ]
        );
    }

    /**
     * Test processAllSharedCatalogPermissions method.
     *
     * @return void
     */
    public function testProcessAllSharedCatalogPermissions()
    {
        $websiteId = 1;
        $categoryIds = [10, 11];
        $groupIds = [2, 3];
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->once())->method('addFieldToFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID, $websiteId)
            ->willReturnSelf();
        $permissionCollection->expects($this->atLeastOnce())
            ->method('getColumnValues')
            ->withConsecutive(
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID],
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID]
            )
            ->willReturnOnConsecutiveCalls($categoryIds, $groupIds);
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')
            ->with($categoryIds, $groupIds);

        $this->catalogPermissionManagement->processAllSharedCatalogPermissions($websiteId);
    }

    /**
     * Test getAllowedCategoriesIds method.
     *
     * @return void
     */
    public function testGetAllowedCategoriesIds()
    {
        $sharedCatalogId = 242;
        $websiteId = 4;
        $customerGroupId = 13;
        $categoryIds = [12, 13];
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('checkSharedCatalogExist')
            ->with($sharedCatalogId)
            ->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                    13
                ],
                [
                    \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_PERMISSION,
                    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
                ],
                [
                    \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                    4
                ]
            )
            ->willReturnSelf();
        $permissionCollection->expects($this->atLeastOnce())
            ->method('getColumnValues')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID)
            ->willReturn($categoryIds);

        $this->assertEquals(
            $categoryIds,
            $this->catalogPermissionManagement->getAllowedCategoriesIds($sharedCatalogId, $websiteId)
        );
    }

    /**
     * Test reassignForRootCategories method.
     *
     * @return void
     */
    public function testReassignForRootCategories()
    {
        $categoryId = 24;
        $groupId = 234;
        $groupIds = [$groupId];
        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepository->expects($this->once())->method('get')->willReturn($category);
        $category->expects($this->once())->method('getChildren')->willReturn((string) $categoryId);
        $this->sharedCatalogPermissionCollectionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                    ['null' => null]
                ],
                [
                    \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $groupId
                ]
            )
            ->willReturnSelf();
        $permissionCollection->expects($this->atLeastOnce())
            ->method('addFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId)
            ->willReturnSelf();
        $permissionCollection->expects($this->atLeastOnce())->method('getFirstItem')->willReturn($permission);
        $permission->expects($this->atLeastOnce())->method('isObjectNew')->willReturn(false);
        $permission->expects($this->atLeastOnce())
            ->method('getPermission')
            ->willReturnOnConsecutiveCalls(
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            );
        $permission->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $permission->expects($this->atLeastOnce())->method('addData')->willReturnSelf();
        $permission->expects($this->atLeastOnce())->method('setCategoryId')->with($categoryId)->willReturnSelf();
        $permission->expects($this->atLeastOnce())->method('save')->willReturnSelf();
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')
            ->with([24 => 24], array_merge($groupIds, ['null']));

        $this->catalogPermissionManagement->reassignForRootCategories($groupIds, null);
    }

    /**
     * Test updateCategoryPermissions method.
     *
     * @return void
     */
    public function testUpdateCategoryPermissions()
    {
        $categoryId = 24;
        $groupId = 234;
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection = $this->getMockBuilder(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $categoryPermission = $this->getMockBuilder(\Magento\CatalogPermissions\Model\Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCategoryId', 'addData', 'preparePermission', 'save', 'getId'])
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with(
                \Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                ['in' => [$groupId]]
            )
            ->willReturnSelf();
        $permissionCollection->expects($this->once())
            ->method('addFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId)
            ->willReturnSelf();
        $permissionCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$permission]);
        $permission->expects($this->once())
            ->method('getPermission')
            ->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW);
        $permission->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($groupId);
        $this->permissionCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('customer_group_id', $groupId)
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('addFilter')
            ->with('category_id', $categoryId)
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($categoryPermission);
        $categoryPermission->expects($this->once())->method('getId')->willReturn(1);
        $categoryPermission->expects($this->once())->method('addData')->willReturnSelf();
        $categoryPermission->expects($this->once())->method('preparePermission')->willReturnSelf();
        $categoryPermission->expects($this->once())->method('setCategoryId')->with($categoryId)->willReturnSelf();
        $categoryPermission->expects($this->once())->method('save')->willReturnSelf();

        $this->catalogPermissionManagement->updateCategoryPermissions($categoryId, [$groupId]);
    }

    /**
     * Test setAllowPermissions method.
     *
     * @return void
     */
    public function testSetAllowPermissions()
    {
        $categoryId = 24;
        $groupId = 234;
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $groupId)
            ->willReturnSelf();
        $permissionCollection->expects($this->once())
            ->method('addFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId)
            ->willReturnSelf();
        $permissionCollection->expects($this->once())->method('getFirstItem')->willReturn($permission);
        $permission->expects($this->once())->method('isObjectNew')->willReturn(false);
        $permission->expects($this->once())
            ->method('getPermission')
            ->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY);
        $permission->expects($this->once())->method('getId')->willReturn(1);
        $permission->expects($this->once())->method('addData')->willReturnSelf();
        $permission->expects($this->once())->method('setCategoryId')->with($categoryId)->willReturnSelf();
        $permission->expects($this->once())->method('save')->willReturnSelf();
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')
            ->with([$categoryId], [$groupId]);

        $this->catalogPermissionManagement->setAllowPermissions([$categoryId], [$groupId]);
    }

    /**
     * Test setDenyPermissions method.
     *
     * @return void
     */
    public function testSetDenyPermissions()
    {
        $categoryId = 24;
        $groupId = 234;
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $groupId)
            ->willReturnSelf();
        $permissionCollection->expects($this->once())
            ->method('addFilter')
            ->with(\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId)
            ->willReturnSelf();
        $permissionCollection->expects($this->once())->method('getFirstItem')->willReturn($permission);
        $permission->expects($this->once())->method('isObjectNew')->willReturn(false);
        $permission->expects($this->once())
            ->method('getPermission')
            ->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW);
        $permission->expects($this->once())->method('getId')->willReturn(1);
        $permission->expects($this->once())->method('addData')->willReturnSelf();
        $permission->expects($this->once())->method('setCategoryId')->with($categoryId)->willReturnSelf();
        $permission->expects($this->once())->method('save')->willReturnSelf();
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')
            ->with([$categoryId], [$groupId]);

        $this->catalogPermissionManagement->setDenyPermissions([$categoryId], [$groupId]);
    }

    /**
     * Test removeAllPermissions method.
     *
     * @param int|null $storeId
     * @param int|array $websiteId
     * @param int $count
     * @return void
     * @dataProvider removeAllPermissionsDataProvider
     */
    public function testRemoveAllPermissions($storeId, $websiteId, $count)
    {
        $sharedCatalogId = 1;
        $groupId = 4;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeGroup = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('checkSharedCatalogExist')
            ->with($sharedCatalogId)
            ->willReturn($sharedCatalog);
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getIterator', 'addFieldToFilter'])
            ->getMock();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($groupId);
        $sharedCatalog->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManager->expects($this->exactly($count))
            ->method('getGroup')
            ->with($storeId)
            ->willReturn($storeGroup);
        $storeGroup->expects($this->exactly($count))->method('getWebsiteId')->willReturn($websiteId);
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $groupId],
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID, $websiteId]
            )
            ->willReturnSelf();
        $permissionCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$permission]));
        $permission->expects($this->once())->method('delete')->willReturnSelf();

        $this->catalogPermissionManagement->removeAllPermissions($sharedCatalogId);
    }

    /**
     * Data provider for removeAllPermissions method.
     *
     * @return array
     */
    public function removeAllPermissionsDataProvider()
    {
        return [
            [2, 2, 1],
            [null, ['null' => null], 0]
        ];
    }

    /**
     * Test updateSharedCatalogPermission method.
     *
     * @return void
     */
    public function testUpdateSharedCatalogPermission()
    {
        $categoryId = 1;
        $groupId = 1;
        $permissionIndex = -1;
        $permission = $this->getMockBuilder(\Magento\SharedCatalog\Model\Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $permissionCollection = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($permissionCollection);
        $permissionCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId],
                [\Magento\SharedCatalog\Model\Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $groupId]
            )
            ->willReturnSelf();
        $permissionCollection->expects($this->once())->method('getFirstItem')->willReturn($permission);
        $permission->expects($this->once())->method('isObjectNew')->willReturn(false);
        $permission->expects($this->once())->method('setPermission')->with($permissionIndex)->willReturnSelf();
        $permission->expects($this->once())->method('save')->willReturnSelf();

        $this->catalogPermissionManagement->updateSharedCatalogPermission($categoryId, $groupId, $permissionIndex);
    }

    /**
     * Test for setPermissionsForAllCategories method.
     *
     * @param int $websiteId
     * @param int $websiteCalls
     * @param bool $singleStore
     * @param int $filterCount
     * @return void
     * @dataProvider setPermissionsForAllCategoriesDataProvider
     */
    public function testSetPermissionsForAllCategories($websiteId, $websiteCalls, $singleStore, $filterCount)
    {
        $rootCategoryId = 1;
        $childrenCategoriesIds = [2, 3];
        $customerGroupId = 4;
        $permissionId = 5;
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->setMethods(['getRootCategoryId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->exactly($websiteCalls))
            ->method('getGroup')->with($websiteCalls ? $customerGroupId : null)->willReturn($customerGroup);
        $this->storeManager->expects($this->once())
            ->method('hasSingleStore')->willReturn($singleStore);
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->storeManager->expects($this->exactly($websiteCalls))
            ->method('getWebsite')->with($websiteId)->willReturn($website);
        $website->expects($this->exactly($websiteCalls))->method('getDefaultGroupId')->willReturn($customerGroupId);
        $customerGroup->expects($this->exactly($websiteCalls))->method('getRootCategoryId')
            ->willReturn($rootCategoryId);
        $rootCategory = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())
            ->method('get')->with($rootCategoryId)->willReturn($rootCategory);
        $rootCategory->expects($this->once())->method('getAllChildren')->with(true)->willReturn($childrenCategoriesIds);
        $this->customerGroupManagement->expects($this->once())
            ->method('getSharedCatalogGroupIds')->willReturn([$customerGroupId]);
        $permissionCollection = $this
            ->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->permissionCollectionFactory
            ->expects($this->once())->method('create')->willReturn($permissionCollection);
        $permissionCollection->expects($this->exactly($filterCount))->method('addFieldToFilter')
            ->with(
                PermissionInterface::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                $websiteCalls ? $websiteId : ['null' => null]
            )->willReturnSelf();
        $permission = $this->getMockBuilder(\Magento\CatalogPermissions\Model\Permission::class)
            ->setMethods(['getCategoryId', 'getCustomerGroupId', 'getGrantCatalogCategoryView'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $permissionCollection->expects($this->once())->method('getItems')->willReturn([$permission]);
        $permission->expects($this->once())->method('getCategoryId')->willReturn(2);
        $permission->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $permission->expects($this->once())->method('getGrantCatalogCategoryView')
            ->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW);
        $sharedCatalogPermissionCollection = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->atLeastOnce())
            ->method('create')->willReturn($sharedCatalogPermissionCollection);
        $sharedCatalogPermissionCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with(PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $sharedCatalogPermissionCollection->expects($this->atLeastOnce())->method('addFilter')
            ->withConsecutive(
                [PermissionInterface::SHARED_CATALOG_PERMISSION_CATEGORY_ID, 2],
                [PermissionInterface::SHARED_CATALOG_PERMISSION_CATEGORY_ID, 3]
            )
            ->willReturnSelf();
        $sharedCatalogPermission = $this->getMockBuilder(PermissionInterface::class)
            ->setMethods(['isObjectNew', 'getId', 'addData', 'setCategoryId', 'save'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $sharedCatalogPermissionCollection->expects($this->atLeastOnce())
            ->method('getFirstItem')->willReturn($sharedCatalogPermission);
        $sharedCatalogPermission->expects($this->atLeastOnce())
            ->method('isObjectNew')->willReturnOnConsecutiveCalls(false, true);
        $sharedCatalogPermission->expects($this->once())
            ->method('getPermission')->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW);
        $sharedCatalogPermission->expects($this->once())->method('getId')->willReturn($permissionId);
        $sharedCatalogPermission->expects($this->once())->method('addData')->with(
            [
                PermissionInterface::SHARED_CATALOG_PERMISSION_ID => $permissionId,
                PermissionInterface::SHARED_CATALOG_PERMISSION_WEBSITE_ID => null,
                PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID => $customerGroupId,
                PermissionInterface::SHARED_CATALOG_PERMISSION_PERMISSION =>
                    \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            ]
        )->willReturnSelf();
        $sharedCatalogPermission->expects($this->once())->method('setCategoryId')->with(3)->willReturnSelf();
        $sharedCatalogPermission->expects($this->once())->method('save')->willReturnSelf();
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')->with($childrenCategoriesIds, [$customerGroupId]);
        $this->catalogPermissionManagement->setPermissionsForAllCategories($websiteId);
    }

    /**
     * Test for setDenyPermissionsForCustomerGroup method.
     *
     * @return void
     */
    public function testSetDenyPermissionsForCustomerGroup()
    {
        $rootCategoryId = 1;
        $childrenCategoriesIds = [2, 3];
        $customerGroupId = 4;
        $rootCategory = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->setMethods(['getAllChildren'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryRepository->expects($this->once())
            ->method('get')->with($rootCategoryId)->willReturn($rootCategory);
        $rootCategory->expects($this->once())->method('getAllChildren')->with(true)->willReturn($childrenCategoriesIds);
        $this->permissionResource->expects($this->once())->method('addPermissions')->with(
            [
                [
                    PermissionInterface::SHARED_CATALOG_PERMISSION_CATEGORY_ID => 2,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_WEBSITE_ID => null,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID => $customerGroupId,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_PERMISSION =>
                        \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
                ],
                [
                    PermissionInterface::SHARED_CATALOG_PERMISSION_CATEGORY_ID => 3,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_WEBSITE_ID => null,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID => $customerGroupId,
                    PermissionInterface::SHARED_CATALOG_PERMISSION_PERMISSION =>
                        \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
                ]
            ]
        )->willReturn(1);
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')->with($childrenCategoriesIds, [$customerGroupId]);
        $this->catalogPermissionManagement->setDenyPermissionsForCustomerGroup($customerGroupId);
    }

    /**
     * Test for setDenyPermissionsForCategory method.
     *
     * @return void
     */
    public function testSetDenyPermissionsForCategory()
    {
        $customerGroupId = 1;
        $permissionId = 2;
        $categoryId = 3;
        $this->customerGroupManagement->expects($this->once())
            ->method('getSharedCatalogGroupIds')->willReturn([$customerGroupId]);
        $sharedCatalogPermissionCollection = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogPermissionCollectionFactory->expects($this->atLeastOnce())
            ->method('create')->willReturn($sharedCatalogPermissionCollection);
        $sharedCatalogPermissionCollection->expects($this->once())->method('addFieldToFilter')
            ->with(PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $sharedCatalogPermissionCollection->expects($this->once())->method('addFilter')
            ->with(PermissionInterface::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId)
            ->willReturnSelf();
        $sharedCatalogPermission = $this->getMockBuilder(PermissionInterface::class)
            ->setMethods(['isObjectNew', 'getId', 'addData', 'setCategoryId', 'save'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $sharedCatalogPermissionCollection->expects($this->atLeastOnce())
            ->method('getFirstItem')->willReturn($sharedCatalogPermission);
        $sharedCatalogPermission->expects($this->atLeastOnce())
            ->method('isObjectNew')->willReturnOnConsecutiveCalls(false, true);
        $sharedCatalogPermission->expects($this->once())
            ->method('getPermission')->willReturn(\Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW);
        $sharedCatalogPermission->expects($this->once())->method('getId')->willReturn($permissionId);
        $sharedCatalogPermission->expects($this->once())->method('addData')->with(
            [
                PermissionInterface::SHARED_CATALOG_PERMISSION_ID => $permissionId,
                PermissionInterface::SHARED_CATALOG_PERMISSION_WEBSITE_ID => null,
                PermissionInterface::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID => $customerGroupId,
                PermissionInterface::SHARED_CATALOG_PERMISSION_PERMISSION =>
                    \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            ]
        )->willReturnSelf();
        $sharedCatalogPermission->expects($this->once())->method('setCategoryId')->with($categoryId)->willReturnSelf();
        $sharedCatalogPermission->expects($this->once())->method('save')->willReturnSelf();
        $this->sharedCatalogScheduler->expects($this->once())
            ->method('scheduleCategoryPermissionsUpdate')->with([$categoryId], [$customerGroupId]);
        $this->catalogPermissionManagement->setDenyPermissionsForCategory($categoryId);
    }

    /**
     * Data provider for testSetPermissionsForAllCategories.
     *
     * @return array
     */
    public function setPermissionsForAllCategoriesDataProvider()
    {
        return [
            [null, 0, true, 0],
            [9, 1, true, 0],
            [null, 0, false, 1],
            [9, 1, false, 1],
        ];
    }
}
