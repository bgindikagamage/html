<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Model\Company\SaveHandler\Item;

use Magento\Company\Model\SaveHandlerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Company remove forbidden quote items save handler.
 */
class Delete implements SaveHandlerInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete
     */
    private $itemDeleter;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Company\Api\CompanyHierarchyInterface
     */
    private $companyHierarchy;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $sharedCatalogProductItemRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement
     * @param \Magento\SharedCatalog\Model\Config $config
     * @param \Magento\Company\Api\CompanyHierarchyInterface $companyHierarchy
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $sharedCatalogProductItemRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter,
        \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement,
        \Magento\SharedCatalog\Model\Config $config,
        \Magento\Company\Api\CompanyHierarchyInterface $companyHierarchy
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sharedCatalogProductItemRepository = $sharedCatalogProductItemRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->itemDeleter = $itemDeleter;
        $this->quoteManagement = $quoteManagement;
        $this->config = $config;
        $this->companyHierarchy = $companyHierarchy;
    }

    /**
     * @inheritdoc
     */
    public function execute(CompanyInterface $company, CompanyInterface $initialCompany)
    {
        if ($initialCompany->getCustomerGroupId() != $company->getCustomerGroupId()) {
            $productIdsToRemove = array_diff(
                $this->retrieveProductIds($initialCompany->getCustomerGroupId()),
                $this->retrieveProductIds($company->getCustomerGroupId())
            );
            $quoteItems = $this->quoteManagement->retrieveQuoteItemsForCustomers(
                $this->getCompanyCustomerIds($company->getId()),
                $productIdsToRemove,
                $this->config->getActiveSharedCatalogStoreIds()
            );
            $this->itemDeleter->deleteItems($quoteItems);
        }
    }

    /**
     * Retrieve customer ids for company.
     *
     * @param int $companyId
     * @return array
     */
    private function getCompanyCustomerIds($companyId)
    {
        $customerIds = [];
        $hierarchy = $this->companyHierarchy->getCompanyHierarchy($companyId);
        foreach ($hierarchy as $item) {
            if ($item->getEntityType() == \Magento\Company\Api\Data\HierarchyInterface::TYPE_CUSTOMER) {
                $customerIds[] = $item->getEntityId();
            }
        }

        return $customerIds;
    }

    /**
     * Retrieve product ids by customer group id.
     *
     * @param int $customerGroupId
     * @return array
     */
    private function retrieveProductIds($customerGroupId)
    {
        $this->searchCriteriaBuilder->addFilter(
            ProductItemInterface::CUSTOMER_GROUP_ID,
            $customerGroupId,
            'eq'
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->sharedCatalogProductItemRepository->getList($searchCriteria);
        $productSkus = [];
        foreach ($searchResults->getItems() as $item) {
            $productSkus[] = $item->getSku();
        }
        $products = [];
        if ($productSkus) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addFieldToFilter(ProductInterface::SKU, ['in' => $productSkus]);
            foreach ($productCollection as $product) {
                $products[] = $product->getEntityId();
            }
        }

        return $products;
    }
}
