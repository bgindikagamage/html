<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model;

use \Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory;

/**
 * Handle category management for shared catalog.
 */
class CatalogPermissionManagement
{
    /**
     * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory
     */
    private $permissionCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher
     */
    private $sharedCatalogScheduler;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory
     */
    private $sharedCatalogPermissionCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission
     */
    private $permissionResource;

    /**
     * @param \Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory $permissionCollectionFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
     * @param \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher $sharedCatalogScheduler
     * @param CollectionFactory $sharedCatalogPermissionCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\SharedCatalog\Model\ResourceModel\Permission $permissionResource
     */
    public function __construct(
        \Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory $permissionCollectionFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation,
        \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher $sharedCatalogScheduler,
        CollectionFactory $sharedCatalogPermissionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\SharedCatalog\Model\ResourceModel\Permission $permissionResource
    ) {
        $this->permissionCollectionFactory = $permissionCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
        $this->sharedCatalogScheduler = $sharedCatalogScheduler;
        $this->sharedCatalogPermissionCollectionFactory = $sharedCatalogPermissionCollectionFactory;
        $this->storeManager = $storeManager;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->permissionResource = $permissionResource;
    }

    /**
     * Process data for bulk publish all shared catalog categories of website.
     *
     * @param int|null $websiteId
     * @return void
     */
    public function processAllSharedCatalogPermissions($websiteId)
    {
        /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                !$websiteId ? ['null' => null] : $websiteId
            );
        $categoryIds = $permissionCollection->getColumnValues(Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID);
        $groupIds = $permissionCollection->getColumnValues(Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID);
        $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate($categoryIds, $groupIds);
    }

    /**
     * Get array of categories IDs with allowed permissions for provided shared catalog ID and website ID.
     *
     * @param int $sharedCatalogId
     * @param int $websiteId
     * @return array
     */
    public function getAllowedCategoriesIds($sharedCatalogId, $websiteId)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($sharedCatalogId);
        $groupId = $sharedCatalog->getCustomerGroupId();
        /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                $groupId === null ? ['null' => null] : $groupId
            )
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_PERMISSION,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            )
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                !$websiteId ? ['null' => null] : $websiteId
            );
        $categoryIds = $permissionCollection->getColumnValues(Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID);
        return $categoryIds;
    }

    /**
     * Set category permissions.
     *
     * @param int $categoryId
     * @param array $groupIds
     * @param int $permission
     * @return bool
     */
    private function setSharedCategoryPermissions($categoryId, array $groupIds, $permission)
    {
        $permissionWasChanged = false;
        foreach ($groupIds as $groupId) {
            /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
            $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
            $permissionCollection
                ->addFieldToFilter(
                    Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                    $groupId === null ? ['null' => $groupId] : $groupId
                )
                ->addFilter(Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId);
            /** @var Permission $categoryPermissions */
            $categoryPermissions = $permissionCollection->getFirstItem();
            if ($categoryPermissions->isObjectNew() || $categoryPermissions->getPermission() != $permission) {
                $data[Permission::SHARED_CATALOG_PERMISSION_ID] = $categoryPermissions->getId();
                $data[Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID] = null;
                $data[Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID] = $groupId;
                $data[Permission::SHARED_CATALOG_PERMISSION_PERMISSION] = $permission;

                $categoryPermissions->addData($data)->setCategoryId($categoryId)->save();
                $permissionWasChanged = true;
            }
        }
        return $permissionWasChanged;
    }

    /**
     * Reassign category permissions for customer groups that are not linked to any shared catalog.
     *
     * @param array $customerGroupIds
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface|null $sharedCatalog [optional]
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function reassignForRootCategories(array $customerGroupIds, $sharedCatalog = null)
    {
        if ($sharedCatalog === null) {
            $categoryIds = explode(
                ',',
                $this->categoryRepository->get(\Magento\Catalog\Model\Category::TREE_ROOT_ID)->getChildren()
            );
            $updatedCategoriesIds = [];
            foreach ($categoryIds as $categoryId) {
                if ($this->setSharedCategoryPermissions(
                    $categoryId,
                    [null],
                    \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
                )) {
                    $updatedCategoriesIds[$categoryId] = $categoryId;
                }
                if ($this->setSharedCategoryPermissions(
                    $categoryId,
                    $customerGroupIds,
                    \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
                )) {
                    $updatedCategoriesIds[$categoryId] = $categoryId;
                }
            }
            if ($updatedCategoriesIds) {
                $customerGroupIds = array_merge($customerGroupIds, ['null']);
                $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate(
                    $updatedCategoriesIds,
                    $customerGroupIds
                );
            }
        }
    }

    /**
     * Update category permissions. Do not reindex new permissions, used for scheduled job.
     *
     * @param int $categoryId
     * @param array $groupIds
     * @return void
     */
    public function updateCategoryPermissions($categoryId, array $groupIds)
    {
        /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                ['in' => $groupIds]
            )
            ->addFilter(Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID, $categoryId);

        /** @var \Magento\SharedCatalog\Model\Permission $sharedCategoryPermissions */
        foreach ($permissionCollection->getItems() as $sharedCategoryPermissions) {
            $this->setUpdatedCategoryPermission(
                $categoryId,
                $sharedCategoryPermissions->getCustomerGroupId(),
                $sharedCategoryPermissions->getPermission()
            );
        }
    }

    /**
     * Update category permission for specified group.
     *
     * @param int $categoryId
     * @param int|null $groupId
     * @param int $permission
     * @return void
     */
    private function setUpdatedCategoryPermission($categoryId, $groupId, $permission)
    {
        /** @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->permissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter('customer_group_id', $groupId)
            ->addFilter('category_id', $categoryId);
        /** @var \Magento\CatalogPermissions\Model\Permission $categoryPermissions */
        $categoryPermissions = $permissionCollection->getFirstItem();
        $data['permission_id'] = $categoryPermissions->getId();
        $data['website_id'] = null;
        $data['customer_group_id'] = $groupId;
        $data['grant_catalog_category_view'] = $permission;
        $data['grant_catalog_product_price'] = $permission;
        $data['grant_checkout_items'] = $permission;
        $categoryPermissions->addData($data)->preparePermission()->setCategoryId($categoryId)->save();
    }

    /**
     * Set allow category permissions.
     *
     * @param array $categoryIds
     * @param array $groupIds
     * @param bool $scheduleRequired Apply if update should be scheduled to category permissions [optional]
     * @return array
     */
    public function setAllowPermissions(array $categoryIds, array $groupIds, $scheduleRequired = true)
    {
        $updatedCategoryIds = [];
        foreach ($categoryIds as $categoryId) {
            if ($this->setSharedCategoryPermissions(
                $categoryId,
                $groupIds,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            )) {
                $updatedCategoryIds[] = $categoryId;
            }
        }
        if ($scheduleRequired && $updatedCategoryIds) {
            $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate($categoryIds, $groupIds);
        }
        return $updatedCategoryIds;
    }

    /**
     * Set deny category permissions.
     *
     * @param array $categoryIds
     * @param array $groupIds
     * @param bool $scheduleRequired Apply if update should be scheduled to category permissions [optional]
     * @return array
     */
    public function setDenyPermissions(array $categoryIds, array $groupIds, $scheduleRequired = true)
    {
        $updatedCategoryIds = [];
        foreach ($categoryIds as $categoryId) {
            if ($this->setSharedCategoryPermissions(
                $categoryId,
                $groupIds,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
            )) {
                $updatedCategoryIds[] = $categoryId;
            }
        }
        if ($scheduleRequired && $updatedCategoryIds) {
            $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate($categoryIds, $groupIds);
        }
        return $updatedCategoryIds;
    }

    /**
     * Set deny category permissions.
     *
     * @param int|null $websiteId
     * @return void
     */
    public function setPermissionsForAllCategories($websiteId)
    {
        $categoryIds = $this->retrieveCategoriesIds($websiteId);
        $groupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();

        $catalogPermissions = $this->retriveCatalogPermissions($websiteId);
        foreach ($categoryIds as $categoryId) {
            foreach ($groupIds as $groupId) {
                if (isset($catalogPermissions[$categoryId][$groupId])) {
                    $permission = $catalogPermissions[$categoryId][$groupId];
                } else {
                    $permission = \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY;
                }
                $this->setSharedCategoryPermissions(
                    $categoryId,
                    [$groupId],
                    $permission
                );
            }
        }
        $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate($categoryIds, $groupIds);
    }

    /**
     * Set deny permissions by customer group for categories without specified permissions.
     *
     * @param int $customerGroupId
     * @return void
     */
    public function setDenyPermissionsForCustomerGroup($customerGroupId)
    {
        $data = [];
        $categoryIds = $this->retrieveCategoriesIds();
        foreach ($categoryIds as $categoryId) {
            $row = [];
            $row[Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID] = $categoryId;
            $row[Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID] = null;
            $row[Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID] = $customerGroupId;
            $row[Permission::SHARED_CATALOG_PERMISSION_PERMISSION] =
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY;
            $data[] = $row;
        }
        if ($this->permissionResource->addPermissions($data)) {
            $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate($categoryIds, [$customerGroupId]);
        }
    }

    /**
     * Get permissions for website.
     *
     * @param int|null $websiteId
     * @return array
     */
    private function retriveCatalogPermissions($websiteId)
    {
        $catalogPermissions = [];
        /** @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->permissionCollectionFactory->create();
        if (!$this->storeManager->hasSingleStore()) {
            $permissionCollection->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                !$websiteId ? ['null' => null] : $websiteId
            );
        }
        foreach ($permissionCollection->getItems() as $item) {
            $catalogPermissions[$item->getCategoryId()][$item->getCustomerGroupId()] =
                $item->getGrantCatalogCategoryView();
        }

        return $catalogPermissions;
    }

    /**
     * Set deny category permissions.
     *
     * @param int $categoryId
     * @return void
     */
    public function setDenyPermissionsForCategory($categoryId)
    {
        $groupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();
        $this->setSharedCategoryPermissions(
            $categoryId,
            $groupIds,
            \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
        );
        $this->sharedCatalogScheduler->scheduleCategoryPermissionsUpdate([$categoryId], $groupIds);
    }

    /**
     * Get all categories IDs for provided store by its ID.
     *
     * @param int|null $websiteId [optional]
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function retrieveCategoriesIds($websiteId = null)
    {
        if (empty($websiteId)) {
            $rootCategoryId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        } else {
            $website = $this->storeManager->getWebsite($websiteId);
            $rootCategoryId = $this->storeManager->getGroup($website->getDefaultGroupId())->getRootCategoryId();
        }
        $rootCategory = $this->categoryRepository->get($rootCategoryId);
        return $rootCategory->getAllChildren(true);
    }

    /**
     * Remove all stored permissions for Shared Catalog.
     *
     * @param int $sharedCatalogId
     * @return void
     */
    public function removeAllPermissions($sharedCatalogId)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($sharedCatalogId);
        $groupId = $sharedCatalog->getCustomerGroupId();
        $storeId = $sharedCatalog->getStoreId();
        if ($storeId === null) {
            $websiteId = null;
        } else {
            $store = $this->storeManager->getGroup($storeId);
            $websiteId = $store->getWebsiteId();
        }
        /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                $groupId
            )
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_WEBSITE_ID,
                $websiteId === null ? ['null' => null] : $websiteId
            );
        foreach ($permissionCollection as $item) {
            $item->delete();
        }
    }

    /**
     * Update Shared Catalog permission after Category Permission save.
     *
     * @param int $categoryId
     * @param int $groupId
     * @param int $permission
     * @return void
     */
    public function updateSharedCatalogPermission($categoryId, $groupId, $permission)
    {
        /** @var \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection $permissionCollection */
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_CATEGORY_ID,
                $categoryId
            )
            ->addFieldToFilter(
                Permission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
                $groupId
            );
        /** @var \Magento\SharedCatalog\Model\Permission $permissionItem */
        $permissionItem = $permissionCollection->getFirstItem();
        if (!$permissionItem->isObjectNew()) {
            $permissionItem->setPermission($permission)->save();
        }
    }
}
