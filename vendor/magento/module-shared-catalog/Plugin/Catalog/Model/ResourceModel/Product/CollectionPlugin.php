<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use \Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin for Product Collection.
 */
class CollectionPlugin
{
    /**
     * Customer session.
     *
     * @var \Magento\Company\Model\CompanyContext
     */
    protected $companyContext;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    protected $customerGroupManagement;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor for ProductCollectionSetVisibility class.
     *
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Magento\SharedCatalog\Model\Config $config
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Company\Model\CompanyContext $companyContext,
        \Magento\SharedCatalog\Model\Config $config,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->companyContext = $companyContext;
        $this->config = $config;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->storeManager = $storeManager;
    }

    /**
     * Join shared catalog product item to product collection.
     *
     * @param ProductCollection $collection
     * @param bool $printQuery [optional]
     * @param bool $logQuery [optional]
     * @return array
     */
    public function beforeLoad(
        ProductCollection $collection,
        $printQuery = false,
        $logQuery = false
    ) {
        $customerGroupId = $this->companyContext->getCustomerGroupId();
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website)
            && !$this->customerGroupManagement->isMasterCatalogAvailable($customerGroupId)
            && !$collection->isLoaded()
        ) {
            $collection->joinTable(
                ['shared_product' => $collection->getTable(
                    \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PRODUCT_ITEM_TABLE_NAME
                )],
                'sku = sku',
                ['customer_group_id'],
                '{{table}}.customer_group_id = \'' . $customerGroupId . '\''
            );
        }
        return [$printQuery, $logQuery];
    }
}
