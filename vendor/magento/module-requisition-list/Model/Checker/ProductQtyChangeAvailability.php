<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model\Checker;

/**
 * Responsible for checking availability of requisition list item 'Qty' input.
 */
class ProductQtyChangeAvailability implements ProductQtyChangeAvailabilityInterface
{
    /**
     * @inheritdoc
     */
    public function isAvailable(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        return true;
    }
}
