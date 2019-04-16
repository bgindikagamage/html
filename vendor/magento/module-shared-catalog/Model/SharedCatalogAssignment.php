<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

/**
 * Preparing sets of products and categories for assignment to shared catalog.
 */
class SharedCatalogAssignment
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Api\ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\SharedCatalog\Api\ProductManagementInterface $productManagement
     * @param \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $sharedCatalogProductItemRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\SharedCatalog\Api\ProductManagementInterface $productManagement,
        \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository,
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $sharedCatalogProductItemRepository,
        \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productManagement = $productManagement;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->sharedCatalogProductItemRepository = $sharedCatalogProductItemRepository;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
    }

    /**
     * Assign products to shared catalog categories.
     *
     * @param int $sharedCatalogId
     * @param array $assignCategoriesIds
     * @return void
     */
    public function assignProductsForCategories($sharedCatalogId, array $assignCategoriesIds)
    {
        $products = $this->getProductsByCategoryIds($assignCategoriesIds);
        if (!empty($products)) {
            $this->productManagement->assignProducts($sharedCatalogId, $products);
        }
    }

    /**
     * Unassign products from shared catalog categories.
     *
     * @param int $sharedCatalogId
     * @param array $unassignCategoriesIds
     * @param array $assignCategoriesIds
     * @return void
     */
    public function unassignProductsForCategories(
        $sharedCatalogId,
        array $unassignCategoriesIds,
        array $assignCategoriesIds
    ) {
        $products = $this->getUnassignProductsByCategoryIds(
            $sharedCatalogId,
            $unassignCategoriesIds,
            $assignCategoriesIds
        );

        if (!empty($products)) {
            $this->productManagement->unassignProducts($sharedCatalogId, $products);
        }
    }

    /**
     * Get categories IDs to be assigned to shared catalog for provided products SKUs.
     *
     * @param array $assignProductsSkus
     * @return array
     */
    public function getAssignCategoryIdsByProductSkus(array $assignProductsSkus)
    {
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $assignProductsSkus, 'in')
            ->create();
        $assignProducts = $this->productRepository->getList($searchCriteria)->getItems();

        return $this->getAssignCategoryIdsByProducts($assignProducts);
    }

    /**
     * Get categories IDs to be assigned to shared catalog for provided products.
     *
     * @param array $assignProducts
     * @return array
     */
    public function getAssignCategoryIdsByProducts(array $assignProducts)
    {
        $assignCategoryIds = [];
        foreach ($assignProducts as $product) {
            $productCategories = $product->getCategoryIds();
            if (!empty($productCategories)) {
                array_push($assignCategoryIds, ...$productCategories);
            }
        }
        return array_unique($assignCategoryIds);
    }

    /**
     * Get products for provided categories IDs.
     *
     * @param array $categoriesIds
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getProductsByCategoryIds(array $categoriesIds)
    {
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('category_id', $categoriesIds, 'in')
            ->create();
        return $this->productRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get products SKUs to be assigned to shared catalog for provided categories IDs.
     *
     * @param array $assignCategoriesIds
     * @return array
     */
    public function getAssignProductSkusByCategoryIds(array $assignCategoriesIds)
    {
        $products = $this->getProductsByCategoryIds($assignCategoriesIds);

        return array_map(
            function ($product) {
                return $product->getSku();
            },
            $products
        );
    }

    /**
     * Get products to be unassigned from shared catalog when categories are unassigned.
     *
     * @param int $sharedCatalogId
     * @param array $unassignCategoriesIds
     * @param array $assignedCategoriesIds
     * @return array
     */
    private function getUnassignProductsByCategoryIds(
        $sharedCatalogId,
        array $unassignCategoriesIds,
        array $assignedCategoriesIds
    ) {
        $sharedCatalog = $this->sharedCatalogRepository->get($sharedCatalogId);
        $assignedCategoriesIds = array_diff($assignedCategoriesIds, $unassignCategoriesIds);
        $unassignProducts = [];
        $this->searchCriteriaBuilder->addFilter('customer_group_id', $sharedCatalog->getCustomerGroupId());
        $searchCriteria = $this->searchCriteriaBuilder->create();
        foreach ($this->sharedCatalogProductItemRepository->getList($searchCriteria)->getItems() as $product) {
            $product = $this->sharedCatalogInvalidation->checkProductExist($product->getSku());
            if (empty(array_intersect($product->getCategoryIds(), $assignedCategoriesIds))) {
                $unassignProducts[] = $product;
            }
        }
        return $unassignProducts;
    }

    /**
     * Get SKUs of products to be unassigned from shared catalog when a category is unselected during shared catalog
     * configuration.
     *
     * @param array $unassignCategoriesIds
     * @param array $assignedCategoriesIds
     * @return array
     */
    public function getProductSkusToUnassign(array $unassignCategoriesIds, array $assignedCategoriesIds)
    {
        $unassignProductsIds = [];
        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('category_id', $unassignCategoriesIds, 'in')
            ->create();
        foreach ($this->productRepository->getList($searchCriteria)->getItems() as $product) {
            if (empty(array_intersect($product->getCategoryIds(), $assignedCategoriesIds))) {
                $unassignProductsIds[] = $product->getSku();
            }
        }

        return $unassignProductsIds;
    }
}
