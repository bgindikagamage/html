<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Indexer;

use Magento\Catalog\Model\Indexer\Product\Price\UpdateIndexInterface;
use Magento\Customer\Api\Data\GroupInterface;

/**
 * Copy index data in the table from default customer group
 */
class CopyPriceIndex implements UpdateIndexInterface
{
    /**
     * @var CopyIndex
     */
    private $copyIndex;

    /**
     * Constructor
     *
     * @param \Magento\SharedCatalog\Model\Indexer\CopyIndex $copyIndex
     */
    public function __construct(
        \Magento\SharedCatalog\Model\Indexer\CopyIndex $copyIndex
    ) {
        $this->copyIndex = $copyIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function update(GroupInterface $group, $isGroupNew)
    {
        if (!$isGroupNew) {
            return;
        }
        $this->copyIndex->copy($group, ['catalog_product_index_price']);
    }
}
