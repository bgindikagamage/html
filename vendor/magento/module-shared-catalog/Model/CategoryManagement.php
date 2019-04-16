<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model;

use Magento\CatalogPermissions\Helper\Data as PermissionsHelper;

/**
 * Handle category management for shared catalog.
 */
class CategoryManagement implements \Magento\SharedCatalog\Api\CategoryManagementInterface
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogAssignment
     */
    private $sharedCatalogAssignment;

    /**
     * @var PermissionsHelper
     */
    private $permissionsHelper;

    /**
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment
     * @param PermissionsHelper $permissionsHelper
     */
    public function __construct(
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment,
        PermissionsHelper $permissionsHelper
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->storeManager = $storeManager;
        $this->sharedCatalogAssignment = $sharedCatalogAssignment;
        $this->permissionsHelper = $permissionsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories($id)
    {
        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $storeId = $sharedCatalog->getStoreId();
        $allCategoriesIds = $this->getAllStoreCategoriesIds($storeId);
        if ($storeId === null) {
            $store = $this->storeManager->getGroup(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        } else {
            $store = $this->storeManager->getGroup($storeId);
        }

        $websiteId = $store->getWebsiteId();
        $allowedCategoriesIds = $this->catalogPermissionManagement->getAllowedCategoriesIds(
            $id,
            $websiteId
        );
        $assignedCategoriesIds = array_intersect($allCategoriesIds, $allowedCategoriesIds);
        $assignedCategoriesIds = array_map(function ($value) {
            return (int)$value;
        }, $assignedCategoriesIds);

        return array_values($assignedCategoriesIds);
    }

    /**
     * Get all categories IDs for provided store by its ID.
     *
     * @param int $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAllStoreCategoriesIds($storeId)
    {
        $store = $this->storeManager->getGroup($storeId);
        if ($storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $rootCategoryId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        } else {
            $rootCategoryId = $store->getRootCategoryId();
        }

        $rootCategory = $this->categoryRepository->get($rootCategoryId);
        return $rootCategory->getAllChildren(true);
    }

    /**
     * {@inheritdoc}
     */
    public function assignCategories($id, array $categories)
    {
        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $assignCategoriesIds = $this->retrieveCategoriesIds($categories);
        $customerGroups = $this->getSharedCatalogCustomerGroups($sharedCatalog);
        $this->catalogPermissionManagement->setAllowPermissions($assignCategoriesIds, $customerGroups);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unassignCategories($id, array $categories)
    {
        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $unassignCategoriesIds = $this->retrieveCategoriesIds($categories);
        $customerGroups = $this->getSharedCatalogCustomerGroups($sharedCatalog);
        $this->catalogPermissionManagement->setDenyPermissions($unassignCategoriesIds, $customerGroups);
        $this->sharedCatalogAssignment
            ->unassignProductsForCategories($id, $unassignCategoriesIds, $this->getCategories($id));

        return true;
    }

    /**
     * Retrieve categories Ids.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface[] $categories
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException If some of the requested categories don't exist
     */
    private function retrieveCategoriesIds(array $categories)
    {
        $categoriesIds = [];
        foreach ($categories as $category) {
            $categoriesIds[] = $category->getId();
        }
        $allCategoriesIds = $this->getAllStoreCategoriesIds(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $nonexistentCategoriesIds = array_diff($categoriesIds, $allCategoriesIds);
        if (!empty($nonexistentCategoriesIds)) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __(
                    'Requested categories don\'t exist: %categoriesIds.',
                    ['categoriesIds' => implode(', ', array_unique($nonexistentCategoriesIds))]
                )
            );
        }
        return $categoriesIds;
    }

    /**
     * Get list of shared catalog customer groups.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @return array
     */
    private function getSharedCatalogCustomerGroups(
        \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
    ) {
        $customerGroups = [$sharedCatalog->getCustomerGroupId()];

        if ($sharedCatalog->getType() == \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC) {
            $customerGroups[] = \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }

        return $customerGroups;
    }
}
