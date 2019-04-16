<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuoteSharedCatalog\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\ObserverInterface;
use Magento\SharedCatalog\Api\StatusInfoInterface as SharedCatalogModuleConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\NegotiableQuote\Model\Config as NegotiableQuoteModuleConfig;

/**
 * Additional actions after saving data to system config.
 */
class DeleteNegotiableQuoteItems implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface
     */
    private $sharedCatalogModuleConfig;

    /**
     * @var \Magento\NegotiableQuote\Model\Config
     */
    private $negotiableQuoteModuleConfig;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface
     */
    private $productItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete
     */
    private $itemDeleter;

    /**
     * @var int
     */
    private $productsPerIteration = 5000;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SharedCatalogModuleConfig $sharedCatalogModuleConfig
     * @param NegotiableQuoteModuleConfig $negotiableQuoteModuleConfig
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $productItemRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter
     * @param int $productsPerIteration [optional]
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SharedCatalogModuleConfig $sharedCatalogModuleConfig,
        NegotiableQuoteModuleConfig $negotiableQuoteModuleConfig,
        \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement,
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $productItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter,
        $productsPerIteration = 5000
    ) {
        $this->storeManager = $storeManager;
        $this->sharedCatalogModuleConfig = $sharedCatalogModuleConfig;
        $this->negotiableQuoteModuleConfig = $negotiableQuoteModuleConfig;
        $this->quoteManagement = $quoteManagement;
        $this->productItemRepository = $productItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->itemDeleter = $itemDeleter;
        $this->productsPerIteration = $productsPerIteration;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $scopeData = $this->getEventScopeData($observer);

        $isSharedCatalogActive = $this->sharedCatalogModuleConfig->isActive(
            $scopeData->getScopeType(),
            $scopeData->getScopeCode()
        );
        $isNegotiableQuoteActive = $this->negotiableQuoteModuleConfig->isActive(
            $scopeData->getScopeType(),
            $scopeData->getScopeCode()
        );

        if ($isSharedCatalogActive && $isNegotiableQuoteActive) {
            $this->deleteItemsFromQuote();
        }
    }

    /**
     * Delete unavailable quote items from negotiable quotes.
     *
     * @return void
     */
    private function deleteItemsFromQuote()
    {
        $stores = $this->sharedCatalogModuleConfig->getActiveSharedCatalogStoreIds();
        $page = 1;
        do {
            $productItems = $this->retrieveAssignedProductItems($page++);
            foreach ($productItems['items'] as $customerGroup => $products) {
                $items = $this->quoteManagement->retrieveQuoteItems($customerGroup, $products, $stores, false);
                $this->itemDeleter->deleteItems($items);
            }
        } while ($page <= ceil($productItems['totalCount'] / $this->productsPerIteration));
    }

    /**
     * Retrieve array of product ids by customer group.
     *
     * @param int $page
     * @return array
     */
    private function retrieveAssignedProductItems($page)
    {
        $this->searchCriteriaBuilder->setPageSize($this->productsPerIteration);
        $this->searchCriteriaBuilder->setCurrentPage($page);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productItemsResult = $this->productItemRepository->getList($searchCriteria);
        /** @var \Magento\SharedCatalog\Api\Data\ProductItemInterface[] $items */
        $items = $productItemsResult->getItems();
        $skus = [];
        foreach ($items as $item) {
            $skus[] = $item->getSku();
        }
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('sku', ['in' => $skus]);
        $products = [];
        foreach ($productCollection as $product) {
            $products[$product->getSku()] = $product->getEntityId();
        }

        $productByCustomerGroup = ['totalCount' => $productItemsResult->getTotalCount(), 'items' => []];
        foreach ($items as $item) {
            if (!empty($products[$item->getSku()])) {
                $productByCustomerGroup['items'][$item->getCustomerGroupId()][] = $products[$item->getSku()];
            }
        }
        unset($items);
        unset($productItemsResult);

        return $productByCustomerGroup;
    }

    /**
     * Prepare scope data.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return DataObject
     */
    private function getEventScopeData(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $scopeData = new DataObject();
        $scopeType = $event->getWebsite()
            ? ScopeInterface::SCOPE_WEBSITES
            : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeData->setScopeType($scopeType);

        $scopeData->setScopeCode('');
        $scopeData->setScopeId(0);
        if ($scopeType === ScopeInterface::SCOPE_WEBSITES) {
            $website = $this->storeManager->getWebsite($event->getWebsite());
            $scopeData->setScopeCode($website->getCode());
            $scopeData->setScopeId($website->getId());
        }

        return $scopeData;
    }
}
