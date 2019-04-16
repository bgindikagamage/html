<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\SaveHandler\SharedCatalog;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Saver for shared catalog. Prepare shared catalog for save and save it to database.
 */
class Save
{
    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog
     */
    private $sharedCatalogResource;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @param \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog $sharedCatalogResource
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     */
    public function __construct(
        \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog $sharedCatalogResource,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        \Magento\Authorization\Model\UserContextInterface $userContext
    ) {
        $this->sharedCatalogResource = $sharedCatalogResource;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->userContext = $userContext;
    }

    /**
     * Save shared catalog to database.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(SharedCatalogInterface $sharedCatalog)
    {
        $this->sharedCatalogResource->save($sharedCatalog);
    }

    /**
     * Prepare shared catalog data before save.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @return void
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepare(SharedCatalogInterface $sharedCatalog)
    {
        if ($sharedCatalog->getCustomerGroupId()) {
            return;
        }
        $currentUserId = $this->userContext->getUserType() == UserContextInterface::USER_TYPE_ADMIN ?
            $this->userContext->getUserId() : null;
        $sharedCatalog->setCreatedBy($currentUserId);
        $customerGroup = $this->customerGroupManagement->createCustomerGroupForSharedCatalog($sharedCatalog);
        $sharedCatalog->setCustomerGroupId($customerGroup->getId());
        $customerGroupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();
        $this->catalogPermissionManagement->reassignForRootCategories($customerGroupIds, $sharedCatalog);
        if ($sharedCatalog->getType() === null) {
            $sharedCatalog->setType(SharedCatalogInterface::TYPE_CUSTOM);
        }
    }
}
