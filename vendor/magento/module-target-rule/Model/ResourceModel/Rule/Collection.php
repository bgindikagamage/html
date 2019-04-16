<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\ResourceModel\Rule;

/**
 * Target rules resource collection model
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection
{
    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\TargetRule\Model\Rule::class, \Magento\TargetRule\Model\ResourceModel\Rule::class);
    }

    /**
     * Run "afterLoad" callback on items if it is applicable
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        foreach ($this->_items as $rule) {
            /* @var $rule \Magento\TargetRule\Model\Rule */
            if (!$this->getFlag('do_not_run_after_load')) {
                $rule->afterLoad();
            }
        }

        parent::_afterLoad();
        return $this;
    }

    /**
     * Add Apply To Product List Filter to Collection
     *
     * @param int|array $applyTo
     * @return $this
     */
    public function addApplyToFilter($applyTo)
    {
        $this->addFieldToFilter('apply_to', $applyTo);
        return $this;
    }

    /**
     * Set Priority Sort order
     *
     * @param string $direction
     * @return $this
     */
    public function setPriorityOrder($direction = self::SORT_ORDER_ASC)
    {
        $this->setOrder('sort_order', $direction);
        return $this;
    }

    /**
     * Add filter by product id to collection
     *
     * @param int $productId
     * @return $this
     */
    public function addProductFilter($productId)
    {
        $this->getSelect()->join(
            ['product_idx' => $this->getTable('magento_targetrule_product')],
            'product_idx.rule_id = main_table.rule_id',
            []
        )->where(
            'product_idx.product_id = ?',
            $productId
        );

        return $this;
    }

    /**
     * Add filter by segment id to collection
     *
     * @param int $segmentId
     * @return $this
     */
    public function addSegmentFilter($segmentId)
    {
        if (!empty($segmentId)) {
            $this->getSelect()->join(
                ['segement_idx' => $this->getTable('magento_targetrule_customersegment')],
                'segement_idx.rule_id = main_table.rule_id',
                []
            )->where(
                'segement_idx.segment_id = ?',
                $segmentId
            );
        } else {
            $this->getSelect()->joinLeft(
                ['segement_idx' => $this->getTable('magento_targetrule_customersegment')],
                'segement_idx.rule_id = main_table.rule_id',
                []
            )->where(
                'segement_idx.segment_id IS NULL'
            );
        }
        return $this;
    }
}
