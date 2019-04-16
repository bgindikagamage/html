<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class for working with module related store configuration.
 */
class Config implements \Magento\SharedCatalog\Api\StatusInfoInterface
{
    /**
     * Xml path for shared catalogs config.
     */
    const CONFIG_SHARED_CATALOG = 'btob/website_configuration/sharedcatalog_active';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $status;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function isActive($scopeType, $scopeCode)
    {
        $scopeCodeIndex = ($scopeCode === null ? -1 : $scopeCode);
        if (!isset($this->status[$scopeType][$scopeCodeIndex])) {
            $this->status[$scopeType][$scopeCodeIndex] = $this->scopeConfig
                ->isSetFlag(self::CONFIG_SHARED_CATALOG, $scopeType, $scopeCode);
        }
        return $this->status[$scopeType][$scopeCodeIndex];
    }

    /**
     * @inheritdoc
     */
    public function getActiveSharedCatalogStoreIds()
    {
        $storeIds = [];
        foreach ($this->storeManager->getWebsites(true) as $website) {
            if (!$this->isActive(ScopeInterface::SCOPE_WEBSITE, $website->getCode())) {
                continue;
            }
            foreach ($website->getStores() as $store) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }
}
