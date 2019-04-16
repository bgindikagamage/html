<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\CatalogPermissions\Block;

use Magento\CatalogPermissions\Model\Permission;

/**
 * Plugin for adding default permissions for new category page.
 */
class PermissionsForNewCategoryPlugin
{
    /**
     * @var \Magento\CatalogPermissions\Model\PermissionFactory
     */
    private $permissionFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface
     */
    private $status;

    /**
     * @param \Magento\CatalogPermissions\Model\PermissionFactory $permissionFactory
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\SharedCatalog\Api\StatusInfoInterface $status
     */
    public function __construct(
        \Magento\CatalogPermissions\Model\PermissionFactory $permissionFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\SharedCatalog\Api\StatusInfoInterface $status
    ) {
        $this->permissionFactory = $permissionFactory;
        $this->serializer = $serializer;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->status = $status;
    }

    /**
     * Add default category permissions for new category.
     *
     * @param \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions $subject
     * @param string $result
     * @return string
     * @throws \InvalidArgumentException
     */
    public function afterGetConfigJson(
        \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions $subject,
        $result
    ) {
        if (!$subject->getCategoryId() && $result && $this->status->getActiveSharedCatalogStoreIds()) {
            $resultDecoded = (array)$this->serializer->unserialize($result);
            if (isset($resultDecoded['permissions'])) {
                $rowId = 1;
                foreach ($this->customerGroupManagement->getSharedCatalogGroupIds() as $groupId) {
                    /** @var Permission $permission */
                    $permission = $this->permissionFactory->create();
                    $permission->setCustomerGroupId($groupId);
                    $permission->setGrantCatalogCategoryView(Permission::PERMISSION_DENY);
                    $permission->setGrantCatalogProductPrice(Permission::PERMISSION_DENY);
                    $permission->setGrantCheckoutItems(Permission::PERMISSION_DENY);
                    $permission->setWebsiteId(null);
                    $resultDecoded['permissions']['permission' . $rowId++] = $permission->getData();
                }
                $resultEncoded = $this->serializer->serialize($resultDecoded);
                if (is_string($resultEncoded)) {
                    $result = $resultEncoded;
                }
            }
        }

        return $result;
    }
}
