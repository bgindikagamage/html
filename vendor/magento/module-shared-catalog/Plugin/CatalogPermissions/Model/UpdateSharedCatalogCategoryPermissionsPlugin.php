<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\CatalogPermissions\Model;

/**
 * Update shared catalog permissions on category permission change.
 */
class UpdateSharedCatalogCategoryPermissionsPlugin
{
    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface
     */
    private $sharedCatalogConfig;

    /**
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param \Magento\SharedCatalog\Api\StatusInfoInterface $sharedCatalogConfig
     */
    public function __construct(
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        \Magento\SharedCatalog\Api\StatusInfoInterface $sharedCatalogConfig
    ) {
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->sharedCatalogConfig = $sharedCatalogConfig;
    }

    /**
     * Update shared catalog category permission after saving catalog category permission.
     *
     * @param \Magento\CatalogPermissions\Model\Permission $subject
     * @param \Magento\CatalogPermissions\Model\Permission $result
     * @return \Magento\CatalogPermissions\Model\Permission
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        \Magento\CatalogPermissions\Model\Permission $subject,
        \Magento\CatalogPermissions\Model\Permission $result
    ) {
        $categoryId = $result->getCategoryId();
        $customerGroupId = $result->getCustomerGroupId();
        $websiteId = $result->getWebsiteId();
        $permission = $result->getGrantCatalogCategoryView();
        if ($this->sharedCatalogConfig->isActive(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId)) {
            $this->catalogPermissionManagement->updateSharedCatalogPermission(
                $categoryId,
                $customerGroupId,
                $permission
            );
        }
        return $result;
    }

    /**
     * Update shared catalog category permission after deleting catalog category permission.
     *
     * @param \Magento\CatalogPermissions\Model\Permission $subject
     * @param \Magento\CatalogPermissions\Model\Permission $result
     * @return \Magento\CatalogPermissions\Model\Permission
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        \Magento\CatalogPermissions\Model\Permission $subject,
        \Magento\CatalogPermissions\Model\Permission $result
    ) {
        $categoryId = $result->getCategoryId();
        $customerGroupId = $result->getCustomerGroupId();
        $websiteId = $result->getWebsiteId();
        if ($this->sharedCatalogConfig->isActive(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId)) {
            $this->catalogPermissionManagement->updateSharedCatalogPermission(
                $categoryId,
                $customerGroupId,
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY
            );
        }
        return $result;
    }
}
