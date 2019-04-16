<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;

/**
 * Class for loading products for shared catalog.
 */
class SharedCatalogProductsLoader
{
    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface
     */
    private $linkRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $skuCache = [];

    /**
     * @param ProductItemRepositoryInterface $linkRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductItemRepositoryInterface $linkRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->linkRepository = $linkRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get SKUs of products that are assigned to the shared catalog.
     *
     * @param int $customerGroupId
     * @return array
     */
    public function getAssignedProductsSkus($customerGroupId)
    {
        if (!isset($this->skuCache[$customerGroupId])) {
            $this->searchCriteriaBuilder->addFilter(ProductItemInterface::CUSTOMER_GROUP_ID, $customerGroupId);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->linkRepository->getList($searchCriteria);
            $skuList = [];
            foreach ($searchResults->getItems() as $link) {
                $skuList[] = $link->getSku();
            }
            $this->skuCache[$customerGroupId] = $skuList;
        }
        return $this->skuCache[$customerGroupId];
    }
}
