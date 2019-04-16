<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\Config;

use Magento\Company\Api\StatusServiceInterface;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class for category permissions configuration check.
 */
class CategoryPermission
{
    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $sharedCatalogStatusService;

    /**
     * @var \Magento\Company\Api\StatusServiceInterface
     */
    private $companyStatusService;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    private $catalogPermissionStatusService;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\SharedCatalog\Model\Config $sharedCatalogStatusService
     * @param StatusServiceInterface $companyStatusService
     * @param \Magento\CatalogPermissions\App\ConfigInterface $catalogPermissionStatusService
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\SharedCatalog\Model\Config $sharedCatalogStatusService,
        StatusServiceInterface $companyStatusService,
        \Magento\CatalogPermissions\App\ConfigInterface $catalogPermissionStatusService,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->sharedCatalogStatusService = $sharedCatalogStatusService;
        $this->companyStatusService = $companyStatusService;
        $this->catalogPermissionStatusService = $catalogPermissionStatusService;
        $this->storeManager = $storeManager;
    }

    /**
     * Check configuration category permission for enable.
     *
     * @return bool
     */
    public function isConfigEnable()
    {
        $result = false;
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->sharedCatalogStatusService->isActive(ScopeInterface::SCOPE_WEBSITE, $website)
            && $this->companyStatusService->isActive()
            && $this->catalogPermissionStatusService->isEnabled()
            && $this->catalogPermissionStatusService->getCatalogCategoryViewMode() == ConfigInterface::GRANT_ALL
        ) {
            $result = true;
        }
        return $result;
    }
}
