<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Duplicating categories and products in a shared catalog.
 */
class Duplicator
{
    /**
     * @var \Magento\SharedCatalog\Api\CategoryManagementInterface
     */
    private $categoryManagement;

    /**
     * @var \Magento\SharedCatalog\Api\ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader
     */
    private $tierPriceLoader;

    /**
     * @param CategoryManagementInterface $categoryManagement
     * @param ProductManagementInterface $productManagement
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param ProductRepositoryInterface $productRepository
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param ScheduleBulk $scheduleBulk
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UserContextInterface $userContextInterface
     * @param \Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader $tierPricesLoader
     */
    public function __construct(
        CategoryManagementInterface $categoryManagement,
        ProductManagementInterface $productManagement,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        ProductRepositoryInterface $productRepository,
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        ScheduleBulk $scheduleBulk,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UserContextInterface $userContextInterface,
        \Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader $tierPricesLoader
    ) {
        $this->categoryManagement = $categoryManagement;
        $this->productManagement = $productManagement;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->productRepository = $productRepository;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->scheduleBulk = $scheduleBulk;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->userContext = $userContextInterface;
        $this->tierPriceLoader = $tierPricesLoader;
    }

    /**
     * Duplicate categories, products and store from shared catalog $idOriginal to shared catalog $idDuplicated.
     *
     * @param int $idOriginal
     * @param int $idDuplicated
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function duplicateCatalog($idOriginal, $idDuplicated)
    {
        $oldCatalog = $this->sharedCatalogRepository->get($idOriginal);
        $newCatalog = $this->sharedCatalogRepository->get($idDuplicated);
        $newCatalog->setStoreId($oldCatalog->getStoreId());
        $this->sharedCatalogRepository->save($newCatalog);

        $categoryIds = $this->categoryManagement->getCategories($idOriginal);
        $this->catalogPermissionManagement->setAllowPermissions(
            $categoryIds,
            [$newCatalog->getCustomerGroupId()]
        );
        $productSkus = $this->productManagement->getProducts($idOriginal);
        $tierPrices = $this->tierPriceLoader->load($productSkus, $oldCatalog->getCustomerGroupId());
        $this->searchCriteriaBuilder->setFilterGroups([]);
        $this->searchCriteriaBuilder->addFilter('sku', $productSkus, 'in');
        $products = $this->productRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        $this->productManagement->assignProducts($idDuplicated, $products);

        if ($tierPrices) {
            $this->scheduleBulk->execute($newCatalog, $tierPrices, $this->userContext->getUserId());
        }
    }
}
