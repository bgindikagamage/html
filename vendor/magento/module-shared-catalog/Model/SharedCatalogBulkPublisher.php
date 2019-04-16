<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Publisher for Category Permissions update queue.
 */
class SharedCatalogBulkPublisher
{
    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $permissionsConfig;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\SharedCatalog\Model\Config $permissionsConfig
     * @param \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk $scheduleBulk
     * @param \Magento\Authorization\Model\UserContextInterface $userContextInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\SharedCatalog\Model\Config $permissionsConfig,
        \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk $scheduleBulk,
        \Magento\Authorization\Model\UserContextInterface $userContextInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->scheduleBulk = $scheduleBulk;
        $this->userContext = $userContextInterface;
        $this->storeManager = $storeManager;
    }

    /**
     * Schedule permissions transfer to Category Permissions table.
     *
     * @param array|int $categoryIds
     * @param array|int $groupIds
     * @return void
     */
    public function scheduleCategoryPermissionsUpdate($categoryIds, $groupIds)
    {
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->permissionsConfig->isActive(ScopeInterface::SCOPE_WEBSITE, $website)) {
            $userId = null;
            if ($this->userContext->getUserType() == UserContextInterface::USER_TYPE_ADMIN) {
                $userId = $this->userContext->getUserId();
            }
            $this->scheduleBulk->execute(
                $categoryIds,
                array_unique($groupIds),
                $userId
            );
        }
    }
}
