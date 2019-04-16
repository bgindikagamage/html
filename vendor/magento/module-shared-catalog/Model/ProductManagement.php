<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

/**
 * Shared catalog products actions.
 */
class ProductManagement implements \Magento\SharedCatalog\Api\ProductManagementInterface
{
    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemManagementInterface
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductSharedCatalogsLoader
     */
    private $productSharedCatalogsLoader;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Api\CategoryManagementInterface
     */
    private $sharedCatalogCategoryManagement;

    /**
     * ProductSharedCatalogsManagement constructor.
     *
     * @param \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param \Magento\SharedCatalog\Api\ProductItemManagementInterface $productItemManagement
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductSharedCatalogsLoader $productSharedCatalogsLoader
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $productItemRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
     * @param \Magento\SharedCatalog\Api\CategoryManagementInterface $sharedCatalogCategoryManagement
     */
    public function __construct(
        \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository,
        \Magento\SharedCatalog\Api\ProductItemManagementInterface $productItemManagement,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductSharedCatalogsLoader $productSharedCatalogsLoader,
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $productItemRepository,
        \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation,
        \Magento\SharedCatalog\Api\CategoryManagementInterface $sharedCatalogCategoryManagement
    ) {
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->sharedCatalogProductItemManagement = $productItemManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productSharedCatalogsLoader = $productSharedCatalogsLoader;
        $this->sharedCatalogProductItemRepository = $productItemRepository;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
        $this->sharedCatalogCategoryManagement = $sharedCatalogCategoryManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts($id)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $this->searchCriteriaBuilder->addFilter(
            \Magento\SharedCatalog\Api\Data\ProductItemInterface::CUSTOMER_GROUP_ID,
            $sharedCatalog->getCustomerGroupId()
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->prepareProductSkus(
            $this->sharedCatalogProductItemRepository->getList($searchCriteria)->getItems()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function assignProducts($id, array $products)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $customerGroupIds[] = $sharedCatalog->getCustomerGroupId();
        $categoryIds = $this->sharedCatalogCategoryManagement->getCategories($sharedCatalog->getId());
        $skus = $this->sharedCatalogInvalidation->validateAssignProducts($products, $categoryIds);
        if ($sharedCatalog->getType() == \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC) {
            $customerGroupIds[] = \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }
        foreach ($customerGroupIds as $customerGroupId) {
            $this->sharedCatalogProductItemManagement->addItems(
                $customerGroupId,
                $skus
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unassignProducts($id, array $products)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $customerGroupIds[] = $sharedCatalog->getCustomerGroupId();
        $skus = $this->sharedCatalogInvalidation->validateUnassignProducts($products);
        if ($sharedCatalog->getType() == \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC) {
            $customerGroupIds[] = \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }
        foreach ($customerGroupIds as $customerGroupId) {
            $this->deleteProductItems($customerGroupId, $skus, 'in');
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductSharedCatalogs($sku, array $sharedCatalogIds)
    {
        $assignedSharedCatalogs = $this->productSharedCatalogsLoader->getAssignedSharedCatalogs($sku);

        $forCreate = array_diff($sharedCatalogIds, array_keys($assignedSharedCatalogs));
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::SHARED_CATALOG_ID, $forCreate, 'in')
            ->create();
        $sharedCatalogs = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
        foreach ($sharedCatalogs as $sharedCatalog) {
            $this->sharedCatalogProductItemManagement->saveItem($sku, $sharedCatalog->getCustomerGroupId());
        }

        $forDelete = array_diff_key($assignedSharedCatalogs, array_flip($sharedCatalogIds));
        foreach ($forDelete as $sharedCatalog) {
            $this->sharedCatalogProductItemManagement->deleteItems($sharedCatalog, [$sku]);
        }
    }

    /**
     * Reassign products to shared catalog.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @param array $skus
     * @return $this
     */
    public function reassignProducts(
        \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog,
        array $skus
    ) {
        $customerGroupIds[] = $sharedCatalog->getCustomerGroupId();
        if ($sharedCatalog->getType() == \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC) {
            $customerGroupIds[] = \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }
        foreach ($customerGroupIds as $customerGroupId) {
            $this->deleteProductItems($customerGroupId, $skus);
            $this->sharedCatalogProductItemManagement->addItems($customerGroupId, $skus);
        }

        return $this;
    }

    /**
     * Delete product items from shared catalog.
     *
     * @param int $customerGroupId
     * @param array $skus [optional]
     * @param string $conditionType [optional]
     * @return $this
     */
    private function deleteProductItems($customerGroupId, array $skus = [], $conditionType = 'nin')
    {
        $this->searchCriteriaBuilder->addFilter(
            \Magento\SharedCatalog\Api\Data\ProductItemInterface::CUSTOMER_GROUP_ID,
            $customerGroupId
        );
        if (!empty($skus)) {
            $this->searchCriteriaBuilder->addFilter(
                \Magento\SharedCatalog\Api\Data\ProductItemInterface::SKU,
                $skus,
                $conditionType
            );
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productItems = $this->sharedCatalogProductItemRepository->getList($searchCriteria)->getItems();
        $this->sharedCatalogProductItemRepository->deleteItems($productItems);
        foreach ($productItems as $productItem) {
            $this->sharedCatalogInvalidation->cleanCacheByTag($productItem->getSku());
        }
        $this->sharedCatalogInvalidation->invalidateIndexRegistryItem();
        return $this;
    }

    /**
     * Prepare product skus array.
     *
     * @param \Magento\SharedCatalog\Api\Data\ProductItemInterface[] $products
     * @return string
     */
    private function prepareProductSkus(array $products)
    {
        $productsSkus = [];
        foreach ($products as $product) {
            $productsSkus[] = $product->getSku();
        }

        return $productsSkus;
    }
}
