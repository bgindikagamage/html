<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Indexer\Category\Product;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;

class Preview extends AbstractAction
{
    /**
     * @var int|null
     */
    protected $categoryId;

    /**
     * @var array
     */
    protected $productIds = [];

    /**
     * Prefix for temporary table name
     */
    const TMP_PREFIX = '_catalog_staging_tmp';

    /**
     * @param null $categoryId
     * @param array $productIds
     * @return void
     */
    public function execute($categoryId = null, array $productIds = [])
    {
        $this->categoryId = $categoryId;
        $this->productIds = $productIds;
        $this->prepareTemporaryStorage();
        $this->reindex();
    }

    /**
     * @return void
     */
    protected function prepareTemporaryStorage()
    {
        $this->resource->getConnection()->createTemporaryTableLike(
            $this->resource->getTableName($this->getTemporaryTable()),
            $this->resource->getTableName(static::MAIN_INDEX_TABLE)
        );
    }

    /**
     * @return string
     */
    protected function getMainTmpTable()
    {
        return $this->resource->getTableName($this->getTemporaryTable());
    }

    /**
     * @return string
     */
    public function getTemporaryTable()
    {
        return static::MAIN_INDEX_TABLE . static::TMP_PREFIX;
    }

    /**
     * @return string
     */
    protected function getMainTable()
    {
        return $this->getTemporaryTable();
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getAllProducts(\Magento\Store\Model\Store $store)
    {
        $allProductsSelect = parent::getAllProducts($store);
        $allProductsSelect->where('cp.entity_id IN (?)', $this->productIds);
        return $allProductsSelect;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function createAnchorSelect(\Magento\Store\Model\Store $store)
    {
        $anchorSelect = parent::createAnchorSelect($store);
        $anchorSelect->where('cpe.entity_id IN (?)', $this->productIds);
        $anchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $anchorSelect;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getNonAnchorCategoriesSelect(\Magento\Store\Model\Store $store)
    {
        $nonAnchorSelect = parent::getNonAnchorCategoriesSelect($store);
        $nonAnchorSelect->where('cpe.entity_id IN (?)', $this->productIds);
        $nonAnchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $nonAnchorSelect;
    }
}
