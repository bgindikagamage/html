<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Observer\Controller;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;

/**
 * Add product to the selected shared catalogs after saving.
 */
class SaveProduct implements ObserverInterface
{
    /**
     * @var \Magento\SharedCatalog\Api\ProductManagementInterface
     */
    private $productSharedCatalogManagement;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @param ProductManagementInterface $productSharedCatalogManagement
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param CatalogPermissionManagement $catalogPermissionManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductManagementInterface $productSharedCatalogManagement,
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        CatalogPermissionManagement $catalogPermissionManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productSharedCatalogManagement = $productSharedCatalogManagement;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Add product to the selected shared catalogs after saving.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        $sku = $product->getSku();

        $customerGroupIds = $this->retrieveCustomerGroupIds((array)$product->getData('tier_price'));
        $sharedCatalogIds = $this->prepareSharedCatalogIds(
            (array)$product->getData('shared_catalog'),
            (array)$customerGroupIds
        );
        $this->productSharedCatalogManagement->updateProductSharedCatalogs($sku, $sharedCatalogIds);

        $customerGroupIds = $this->prepareCategoriesIds($sharedCatalogIds);
        $this->catalogPermissionManagement->setAllowPermissions($product->getCategoryIds(), $customerGroupIds);

        return $this;
    }

    /**
     * Prepare list of shared catalog ids.
     *
     * @param array $sharedCatalogsIds
     * @param array $customerGroupIds
     * @return array
     */
    private function prepareSharedCatalogIds(array $sharedCatalogsIds, array $customerGroupIds)
    {
        if ($customerGroupIds) {
            $this->searchCriteriaBuilder->addFilter(
                \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::CUSTOMER_GROUP_ID,
                $customerGroupIds,
                'in'
            );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $sharedCatalogs = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();

            foreach ($sharedCatalogs as $sharedCatalog) {
                if (!in_array($sharedCatalog->getId(), $sharedCatalogsIds)) {
                    $sharedCatalogsIds[] = $sharedCatalog->getId();
                }
            }
        }

        return $sharedCatalogsIds;
    }

    /**
     * Retrieve customer group ids list from tier prices data.
     *
     * @param array $tierPricesData
     * @return array
     */
    private function retrieveCustomerGroupIds(array $tierPricesData)
    {
        $customerGroups = [];

        foreach ($tierPricesData as $tierPrice) {
            if (!isset($tierPrice['delete']) && !empty($tierPrice['cust_group'])) {
                $customerGroups[] = $tierPrice['cust_group'];
            }
        }

        return $customerGroups;
    }

    /**
     * Prepare shared catalog category ids for update permissions.
     *
     * @param array $sharedCatalogIds
     * @return array
     */
    private function prepareCategoriesIds(array $sharedCatalogIds)
    {
        $this->searchCriteriaBuilder->addFilter(
            \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::SHARED_CATALOG_ID,
            $sharedCatalogIds,
            'in'
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $sharedCatalogs = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
        $customerGroupIds = [];
        foreach ($sharedCatalogs as $sharedCatalog) {
            $customerGroupIds[] = $sharedCatalog->getCustomerGroupId();
        }
        $customerGroupIds = array_unique($customerGroupIds);
        return $customerGroupIds;
    }
}
