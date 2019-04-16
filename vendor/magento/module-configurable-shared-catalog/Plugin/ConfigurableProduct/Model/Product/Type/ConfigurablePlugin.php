<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableSharedCatalog\Plugin\ConfigurableProduct\Model\Product\Type;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin for Configurable Product Type for unset products from usedProducts if they not in shared catalog.
 */
class ConfigurablePlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $config;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader
     */
    private $productLoader;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\SharedCatalog\Model\Config $config
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\SharedCatalog\Model\Config $config,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $productLoader,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->config = $config;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->customerRepository = $customerRepository;
        $this->productLoader = $productLoader;
        $this->storeManager = $storeManager;
    }

    /**
     * Unset products from usedProducts if they not in shared catalog.
     *
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $collection
     * @param array $usedProducts
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetUsedProducts(
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $collection,
        array $usedProducts
    ) {
        $customerId = $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website) && !empty($customerId)
            && !empty($usedProducts)
        ) {
            $customer = $this->customerRepository->getById($customerId);
            if (!$this->customerGroupManagement->isMasterCatalogAvailable($customer->getGroupId())) {
                $skus = $this->productLoader->getAssignedProductsSkus($customer->getGroupId());
                foreach ($usedProducts as $key => $product) {
                    if (!in_array($product->getSku(), $skus)) {
                        unset($usedProducts[$key]);
                    }
                }
            }
        }
        return $usedProducts;
    }
}
