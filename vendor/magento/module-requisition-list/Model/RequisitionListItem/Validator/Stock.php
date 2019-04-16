<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model\RequisitionListItem\Validator;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionListItem\ValidatorInterface;
use Magento\RequisitionList\Model\RequisitionListItemProduct;

/**
 * Class is responsible for validation of product stock.
 */
class Stock implements ValidatorInterface
{
    /**
     * Product is out of stock.
     */
    const ERROR_OUT_OF_STOCK = 'out_of_stock';

    /**
     * Requested product quantity is greater than available quantity.
     */
    const ERROR_LOW_QUANTITY = 'low_quantity';

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var RequisitionListItemProduct
     */
    private $requisitionListItemProduct;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param RequisitionListItemProduct $requisitionListItemProduct
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        RequisitionListItemProduct $requisitionListItemProduct
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->requisitionListItemProduct = $requisitionListItemProduct;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(RequisitionListItemInterface $item)
    {
        $errors = [];
        $product = $this->requisitionListItemProduct->getProduct($item);

        /** @var StockItemInterface $stock */
        $stock = $this->stockRegistry->getStockItem($product->getId());
        if ($stock) {
            if (!$stock->getIsInStock()) {
                $errors[self::ERROR_OUT_OF_STOCK] = __('The SKU is out of stock.');
                return $errors;
            }

            if (($stock->getQty() < $item->getQty()) && !$product->isComposite()) {
                $errors[self::ERROR_LOW_QUANTITY] =
                    __('We don\'t have as many "%1" as you requested.', $product->getName());
                return $errors;
            }
        }

        return $errors;
    }
}
