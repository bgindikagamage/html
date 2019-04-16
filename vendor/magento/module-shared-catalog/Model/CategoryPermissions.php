<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Enables category permissions in system configuration on SharedCatalog enabling.
 */
class CategoryPermissions
{
    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    private $configResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var int
     */
    private $defaultScopeId = 0;

    /**
     * @var array
     */
    private $websitesIds;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * @var \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator
     */
    private $invalidator;

    /**
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator|null $invalidator
     */
    public function __construct(
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator $invalidator = null
    ) {
        $this->config = $config;
        $this->configResource = $configResource;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->invalidator = $invalidator ?: ObjectManager::getInstance()->get(
            \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator::class
        );
    }

    /**
     * Enables category permissions.
     *
     * @return void
     */
    public function enable()
    {
        $this->enableCategoryPermissions();

        if (!$this->scopeConfig->getValue(ConfigInterface::XML_PATH_ENABLED)) {
            $this->setAllowBrowsingCategory();
            $this->setDisplayProductPrices();
            $this->setAllowAddingToCart();
        }
        $this->config->reinit();
        $this->invalidator->invalidate();
    }

    /**
     * Switch on category permissions.
     *
     * @return void
     */
    private function enableCategoryPermissions()
    {
        $this->configResource->saveConfig(
            ConfigInterface::XML_PATH_ENABLED,
            1,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->defaultScopeId
        );
    }

    /**
     * Allow browsing category for everyone.
     *
     * @return void
     */
    private function setAllowBrowsingCategory()
    {
        $this->deletePerWebsiteConfigs(ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW);
        $this->configResource->saveConfig(
            ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW,
            ConfigInterface::GRANT_ALL,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->defaultScopeId
        );
    }

    /**
     * Display product prices for everyone.
     *
     * @return void
     */
    private function setDisplayProductPrices()
    {
        $this->deletePerWebsiteConfigs(ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE);
        $this->configResource->saveConfig(
            ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE,
            ConfigInterface::GRANT_ALL,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->defaultScopeId
        );
    }

    /**
     * Allow adding to cart for everyone.
     *
     * @return void
     */
    private function setAllowAddingToCart()
    {
        $this->deletePerWebsiteConfigs(ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS);
        $this->configResource->saveConfig(
            ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS,
            ConfigInterface::GRANT_ALL,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->defaultScopeId
        );
    }

    /**
     * Delete system config values with websites scope.
     *
     * @param string $path
     * @return void
     */
    private function deletePerWebsiteConfigs($path)
    {
        $websitesIds = $this->getAllWebsitesIds();

        foreach ($websitesIds as $websitesId) {
            $this->configResource->deleteConfig($path, ScopeInterface::SCOPE_WEBSITES, $websitesId);
        }
    }

    /**
     * Get list of all websites.
     *
     * @return array
     */
    private function getAllWebsitesIds()
    {
        if ($this->websitesIds === null) {
            $this->websitesIds = [];
            $websites = $this->storeManager->getWebsites();

            foreach ($websites as $website) {
                $this->websitesIds[] = $website->getId();
            }
        }

        return $this->websitesIds;
    }
}
