<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Plugin;

/**
 * Remove products from negotiable quotes if products were unassigned from shared catalog.
 */
class DeleteUnavailableNegotiableQuoteItems
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader
     */
    private $sharedCatalogProductsLoader;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete
     */
    private $itemDeleter;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface
     */
    private $config;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever
     */
    private $sharedCatalogRetriever;

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $sharedCatalogProductsLoader
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter
     * @param \Magento\SharedCatalog\Api\StatusInfoInterface $config
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever $sharedCatalogRetriever
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\SharedCatalog\Model\SharedCatalogProductsLoader $sharedCatalogProductsLoader,
        \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter,
        \Magento\SharedCatalog\Api\StatusInfoInterface $config,
        \Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever $sharedCatalogRetriever
    ) {
        $this->productRepository = $productRepository;
        $this->sharedCatalogProductsLoader = $sharedCatalogProductsLoader;
        $this->quoteManagement = $quoteManagement;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->itemDeleter = $itemDeleter;
        $this->config = $config;
        $this->sharedCatalogRetriever = $sharedCatalogRetriever;
    }

    /**
     * Remove product from negotiable quotes after unassigning product from shared catalog.
     *
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $subject
     * @param bool $result
     * @param \Magento\SharedCatalog\Api\Data\ProductItemInterface $item
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $subject,
        $result,
        \Magento\SharedCatalog\Api\Data\ProductItemInterface $item
    ) {
        if ($result) {
            try {
                $product = $this->productRepository->get($item->getSku());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return $result;
            }
            $quoteItems = $this->quoteManagement->retrieveQuoteItems(
                $item->getCustomerGroupId(),
                [$product->getId()],
                $this->config->getActiveSharedCatalogStoreIds()
            );
            $this->itemDeleter->deleteItems($quoteItems);
        }

        return $result;
    }

    /**
     * Remove products from negotiable quotes after unassigning products from shared catalog.
     *
     * @param \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $subject
     * @param bool $result
     * @param \Magento\SharedCatalog\Api\Data\ProductItemInterface[] $items
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteItems(
        \Magento\SharedCatalog\Api\ProductItemRepositoryInterface $subject,
        $result,
        array $items
    ) {
        if ($result) {
            $skusByGroupId = [];
            foreach ($items as $productItem) {
                $skusByGroupId[$productItem->getCustomerGroupId()][] = $productItem->getSku();
            }

            foreach ($skusByGroupId as $customerGroupId => $productSkus) {
                $products = $this->retrieveProductIds($productSkus, $customerGroupId);
                $quoteItems = $this->quoteManagement->retrieveQuoteItems(
                    $customerGroupId,
                    $products,
                    $this->config->getActiveSharedCatalogStoreIds()
                );
                $this->itemDeleter->deleteItems($quoteItems);
            }
        }

        return $result;
    }

    /**
     * Retrieve product ids by skus.
     *
     * @param array $productSkus
     * @param int $customerGroupId
     * @return array
     */
    private function retrieveProductIds(array $productSkus, $customerGroupId)
    {
        if (!$this->sharedCatalogRetriever->isSharedCatalogPresent($customerGroupId)) {
            $publicCatalog = $this->sharedCatalogRetriever->getPublicCatalog();
            $publicCatalogProductSkus = $this->sharedCatalogProductsLoader->getAssignedProductsSkus(
                $publicCatalog->getCustomerGroupId()
            );
            $productSkus = array_diff($productSkus, $publicCatalogProductSkus);
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('sku', ['in' => $productSkus]);
        $products = [];
        foreach ($productCollection as $product) {
            $products[] = $product->getEntityId();
        }

        return $products;
    }
}
