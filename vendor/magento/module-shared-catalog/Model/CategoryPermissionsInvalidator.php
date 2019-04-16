<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model;

/**
 * Invalidates indexers and cleans cache related to category permissions.
 */
class CategoryPermissionsInvalidator
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        $this->cache = $cache;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Invalidates cache and indexers.
     *
     * @return void
     */
    public function invalidate()
    {
        $this->cache->clean(
            [
                \Magento\Catalog\Model\Category::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Block::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Layout::CACHE_TAG
            ]
        );
        $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)->invalidate();
        $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)->invalidate();
    }
}
