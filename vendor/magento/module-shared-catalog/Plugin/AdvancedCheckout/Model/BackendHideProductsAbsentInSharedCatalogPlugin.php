<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin for AdvancedCheckout Cart model to change item status on not found.
 */
class BackendHideProductsAbsentInSharedCatalogPlugin
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
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $sessionQuote;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader
     * @param \Magento\SharedCatalog\Model\Config $config
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader,
        \Magento\SharedCatalog\Model\Config $config,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productLoader = $productLoader;
        $this->config = $config;
        $this->sessionQuote = $sessionQuote;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Change item code to not found if appropriate product is not in the shared catalog.
     *
     * @param \Magento\AdvancedCheckout\Model\Cart $subject
     * @param array $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheckItem(\Magento\AdvancedCheckout\Model\Cart $subject, array $item)
    {
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website)) {
            $customerId = $this->sessionQuote->getCustomerId();
            if ($customerId) {
                $groupId = $this->customerRepository->getById($customerId)->getGroupId();
            } else {
                $groupId = $subject->getQuote()->getCustomerGroupId();
            }
            $skus = $this->productLoader->getAssignedProductsSkus($groupId);
            if (!in_array($item['sku'], $skus)) {
                $item['code'] = Data::ADD_ITEM_STATUS_FAILED_SKU;
            }
        }
        return $item;
    }
}
