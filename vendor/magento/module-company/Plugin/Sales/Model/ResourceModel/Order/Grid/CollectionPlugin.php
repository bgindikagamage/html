<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Plugin\Sales\Model\ResourceModel\Order\Grid;

/**
 * Add company order extension attribute to order grid collection.
 */
class CollectionPlugin
{
    /**
     * Add company order extension attribute to order grid collection before loading.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $subject
     * @param bool $printQuery [optional]
     * @param bool $logQuery [optional]
     * @return array
     */
    public function beforeLoad(
        \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        $subject->getSelect()
            ->joinLeft(
                ['company_order' => $subject->getTable(
                    \Magento\Company\Setup\InstallSchema::ORDER_ENTITY_TABLE_NAME
                )],
                'main_table.entity_id = company_order.order_id',
                ['company_name']
            );

        return [$printQuery, $logQuery];
    }
}
