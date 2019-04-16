<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Observer;

use Magento\Company\Api\StatusServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\ObserverInterface;
use Magento\SharedCatalog\Model\Config as SharedCatalogModuleConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\SharedCatalog\Model\CategoryPermissions;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ConfigResource;

/**
 * Additional actions after saving data to system config.
 */
class UpdateConfig implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Company\Api\StatusServiceInterface
     */
    private $companyStatusService;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $sharedCatalogModuleConfig;

    /**
     * @var \Magento\SharedCatalog\Model\CategoryPermissions
     */
    private $categoryPermissions;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionsManagement;

    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StatusServiceInterface $companyStatusService
     * @param SharedCatalogModuleConfig $sharedCatalogModuleConfig
     * @param CategoryPermissions $categoryPermissions
     * @param CatalogPermissionManagement $catalogPermissionsManagement
     * @param ConfigResource $configResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StatusServiceInterface $companyStatusService,
        SharedCatalogModuleConfig $sharedCatalogModuleConfig,
        CategoryPermissions $categoryPermissions,
        CatalogPermissionManagement $catalogPermissionsManagement,
        ConfigResource $configResource
    ) {
        $this->storeManager = $storeManager;
        $this->companyStatusService = $companyStatusService;
        $this->sharedCatalogModuleConfig = $sharedCatalogModuleConfig;
        $this->categoryPermissions = $categoryPermissions;
        $this->catalogPermissionsManagement = $catalogPermissionsManagement;
        $this->configResource = $configResource;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $scopeData = $this->getEventScopeData($observer->getEvent());

        $isCompanyActive = $this->companyStatusService->isActive(
            $scopeData->getScopeType(),
            $scopeData->getScopeCode()
        );
        $isSharedCatalogActive = $this->sharedCatalogModuleConfig->isActive(
            $scopeData->getScopeType(),
            $scopeData->getScopeCode()
        );

        if ($isSharedCatalogActive) {
            $this->categoryPermissions->enable();
            $scopeId = $scopeData->getScopeId() ?? null;
            $this->catalogPermissionsManagement->setPermissionsForAllCategories($scopeId);
            $this->catalogPermissionsManagement->processAllSharedCatalogPermissions($scopeId);

            if (!$isCompanyActive) {
                $this->configResource->saveConfig(
                    SharedCatalogModuleConfig::CONFIG_SHARED_CATALOG,
                    false,
                    $scopeData->getScopeType(),
                    $scopeData->getScopeId()
                );
            }
        }
    }

    /**
     * Prepare scope data.
     *
     * @param Event $event
     * @return DataObject
     */
    private function getEventScopeData(Event $event)
    {
        $scopeData = new DataObject();
        $scopeType = $event->getWebsite()
            ? ScopeInterface::SCOPE_WEBSITES
            : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeData->setScopeType($scopeType);

        $scopeData->setScopeCode('');
        $scopeData->setScopeId(0);
        if ($scopeType === ScopeInterface::SCOPE_WEBSITES) {
            $website = $this->storeManager->getWebsite($event->getWebsite());
            $scopeData->setScopeCode($website->getCode());
            $scopeData->setScopeId($website->getId());
        }

        return $scopeData;
    }
}
