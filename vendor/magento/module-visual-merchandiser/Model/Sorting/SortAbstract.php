<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

/**
 * Class SortAbstract
 * @api
 * @since 100.0.2
 */
class SortAbstract
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return int
     */
    protected function getStockId()
    {
        return \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
    }

    /**
     * @return string
     */
    protected function ascOrder()
    {
        return \Magento\Framework\DB\Select::SQL_ASC;
    }

    /**
     * @return string
     */
    protected function descOrder()
    {
        return \Magento\Framework\DB\Select::SQL_DESC;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    protected function addPriceData($collection)
    {
        $connection = $collection->getConnection();
        $select = $collection->getSelect();
        $joinCond = join(
            ' AND ',
            ['price_index.entity_id = e.entity_id']
        );

        $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);

        if (!isset($fromPart['price_index'])) {
            $least = $connection->getLeastSql(['price_index.min_price', 'price_index.tier_price']);
            $minimalExpr = $connection->getCheckSql(
                'price_index.tier_price IS NOT NULL',
                $least,
                'price_index.min_price'
            );
            $specialCheck = $connection->getCheckSql(
                'price_index.price <> price_index.final_price',
                1,
                0
            );
            $colls = [
                'price',
                'tax_class_id',
                'final_price',
                'min_price',
                'max_price',
                'tier_price',
                'special_price' => $specialCheck,
                'minimal_price' => $minimalExpr,
            ];
            $tableName = ['price_index' => $collection->getTable('catalog_product_index_price')];
            $select->joinLeft($tableName, $joinCond, $colls);
        } else {
            $fromPart['price_index']['joinCondition'] = $joinCond;
            $select->setPart(\Magento\Framework\DB\Select::FROM, $fromPart);
        }
    }
}
