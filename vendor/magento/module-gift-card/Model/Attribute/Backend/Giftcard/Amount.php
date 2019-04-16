<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Attribute\Backend\Giftcard;

class Amount extends \Magento\Catalog\Model\Product\Attribute\Backend\Price
{
    /**
     * Giftcard amount backend resource model
     *
     * @var \Magento\GiftCard\Model\ResourceModel\Attribute\Backend\Giftcard\Amount
     */
    protected $_amountResource;

    /**
     * Validate data
     *
     * @param   \Magento\Catalog\Model\Product $object
     * @return  $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate($object)
    {
        $rows = $object->getData($this->getAttribute()->getName());
        if (empty($rows)) {
            return $this;
        }
        $dup = [];

        foreach ($rows as $row) {
            if (!isset($row['price']) || !empty($row['delete'])) {
                continue;
            }

            $key1 = implode('-', [$row['website_id'], $row['price']]);

            if (!empty($dup[$key1])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Duplicate amount found.'));
            }
            $dup[$key1] = 1;
        }
        return $this;
    }
}
