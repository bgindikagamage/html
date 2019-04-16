<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Plugin\Catalog\Api;

/**
 * Class for setting deny permissions for new category.
 */
class DenyPermissionsForNewCategoryPlugin
{
    /**
     * @var \Magento\SharedCatalog\Model\Config\CategoryPermission
     */
    private $configCategoryPermission;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param \Magento\SharedCatalog\Model\Config\CategoryPermission $configCategoryPermission
     */
    public function __construct(
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        \Magento\SharedCatalog\Model\Config\CategoryPermission $configCategoryPermission
    ) {
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->configCategoryPermission = $configCategoryPermission;
    }

    /**
     * Check if product is available for this customer group.
     *
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        \Magento\Catalog\Api\CategoryRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\CategoryInterface $category
    ) {
        if (empty($category->getPermissions()) && $this->configCategoryPermission->isConfigEnable()) {
            $this->catalogPermissionManagement->setDenyPermissionsForCategory($category->getId());
        }
        return $category;
    }
}
