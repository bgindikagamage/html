<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\GiftCard\Model\ResourceModel\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * GiftCard product price indexer resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Price extends \Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice
{
    /**
     * Prepare giftCard products prices in temporary index table
     *
     * @param int|array $entityIds  the entity ids limitation
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareFinalPriceData($entityIds = null)
    {
        $this->_prepareDefaultFinalPriceTable();

        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['e' => $this->getTable('catalog_product_entity')],
            ['entity_id']
        )->join(
            ['cg' => $this->getTable('customer_group')],
            '',
            ['customer_group_id']
        );
        $this->_addWebsiteJoinToSelect($select, true);
        $this->_addProductWebsiteJoinToSelect($select, 'cw.website_id', 'e.entity_id');
        $select->columns(
            ['website_id'],
            'cw'
        )->columns(
            ['tax_class_id' => new \Zend_Db_Expr('0')]
        )->where(
            'e.type_id = ?',
            $this->getTypeId()
        );

        $linkField = $this->getMetadataPool()->getMetadata(ProductInterface::class)->getLinkField();
        // add enable products limitation
        $statusCond = $connection->quoteInto('=?', Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($select, 'status', "e.$linkField", 'cs.store_id', $statusCond, true);

        $allowOpenAmount = $this->_addAttributeToSelect($select, 'allow_open_amount', "e.$linkField", 'cs.store_id');
        $openAmountMin = $this->_addAttributeToSelect($select, 'open_amount_min', "e.$linkField", 'cs.store_id');
        //$openAmounMax    = $this->_addAttributeToSelect($select, 'open_amount_max', 'e.entity_id', 'cs.store_id');

        $attrAmounts = $this->_getAttribute('giftcard_amounts');
        // join giftCard amounts table
        $select->joinLeft(
            ['gca' => $this->getTable('magento_giftcard_amount')],
            "gca.{$linkField} = e.{$linkField} AND gca.attribute_id = " .
            $attrAmounts->getAttributeId() .
            ' AND (gca.website_id = cw.website_id OR gca.website_id = 0)',
            []
        );

        $amountsExpr = 'MIN(' . $connection->getCheckSql('gca.value_id IS NULL', 'NULL', 'gca.value') . ')';

        $openAmountExpr = 'MIN(' . $connection->getCheckSql(
            $allowOpenAmount . ' = 1',
            $connection->getCheckSql($openAmountMin . ' > 0', $openAmountMin, '0'),
            'NULL'
        ) . ')';

        $priceExpr = new \Zend_Db_Expr(
            'ROUND(' . $connection->getCheckSql(
                $openAmountExpr . ' IS NULL',
                $connection->getCheckSql($amountsExpr . ' IS NULL', '0', $amountsExpr),
                $connection->getCheckSql(
                    $amountsExpr . ' IS NULL',
                    $openAmountExpr,
                    $connection->getCheckSql($openAmountExpr . ' > ' . $amountsExpr, $amountsExpr, $openAmountExpr)
                )
            ) . ', 4)'
        );

        $select->group(
            ['e.entity_id', 'cg.customer_group_id', 'cw.website_id']
        )->columns(
            [
                'price' => new \Zend_Db_Expr('NULL'),
                'final_price' => $priceExpr,
                'min_price' => $priceExpr,
                'max_price' => new \Zend_Db_Expr('NULL'),
                'tier_price' => new \Zend_Db_Expr('NULL'),
                'base_tier' => new \Zend_Db_Expr('NULL'),
            ]
        );

        if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        /**
         * Add additional external limitation
         */
        $this->_eventManager->dispatch(
            'prepare_catalog_product_index_select',
            [
                'select' => $select,
                'entity_field' => new \Zend_Db_Expr('e.entity_id'),
                'website_field' => new \Zend_Db_Expr('cw.website_id'),
                'store_field' => new \Zend_Db_Expr('cs.store_id'),
            ]
        );

        $query = $select->insertFromSelect($this->_getDefaultFinalPriceTable());
        $connection->query($query);

        return $this;
    }
}
