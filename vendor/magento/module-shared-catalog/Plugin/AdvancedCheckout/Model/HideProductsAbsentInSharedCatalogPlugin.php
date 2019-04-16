<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin for the AdvancedCheckout Cart model to change item status on not found.
 */
class HideProductsAbsentInSharedCatalogPlugin
{
    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader
     */
    private $productLoader;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader
     * @param \Magento\SharedCatalog\Model\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader,
        \Magento\SharedCatalog\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productLoader = $productLoader;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Change item code to not found if appropriate product is not in the shared catalog.
     *
     * @param \Magento\AdvancedCheckout\Model\Cart $subject
     * @param array $item
     * @return array
     */
    public function afterCheckItem(\Magento\AdvancedCheckout\Model\Cart $subject, array $item)
    {
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website)) {
            $groupId = $subject->getActualQuote()->getCustomerGroupId();
            $skus = $this->productLoader->getAssignedProductsSkus($groupId);
            if (!in_array($item['sku'], $skus)) {
                $item['code'] = Data::ADD_ITEM_STATUS_FAILED_SKU;
            }
        }
        return $item;
    }
}
