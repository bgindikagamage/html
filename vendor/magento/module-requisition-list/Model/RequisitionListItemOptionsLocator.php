<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model;

use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;

/**
 * Provides object with options for the requisition list item.
 */
class RequisitionListItemOptionsLocator
{
    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItemOptionsFactory
     */
    private $requisitionListOptionsItemFactory;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItemProduct
     */
    private $requisitionListItemProduct;

    /**
     * @var \Magento\RequisitionList\Model\OptionsManagement
     */
    private $optionsManagement;

    /**
     * @var array
     */
    private $requisitionListItemOptions = [];

    /**
     * @param \Magento\RequisitionList\Model\RequisitionListItemOptionsFactory $requisitionListOptionsItemFactory
     * @param RequisitionListItemProduct $requisitionListItemProduct
     * @param OptionsManagement $optionsManagement
     */
    public function __construct(
        \Magento\RequisitionList\Model\RequisitionListItemOptionsFactory $requisitionListOptionsItemFactory,
        \Magento\RequisitionList\Model\RequisitionListItemProduct $requisitionListItemProduct,
        \Magento\RequisitionList\Model\OptionsManagement $optionsManagement
    ) {
        $this->requisitionListOptionsItemFactory = $requisitionListOptionsItemFactory;
        $this->requisitionListItemProduct = $requisitionListItemProduct;
        $this->optionsManagement = $optionsManagement;
    }

    /**
     * Get requisition list item option object.
     *
     * @param RequisitionListItemInterface $item
     * @return \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOptions(RequisitionListItemInterface $item)
    {
        $itemId = $item->getId() ?: 0;

        if (!isset($this->requisitionListItemOptions[$itemId])) {
            $product = $this->requisitionListItemProduct->getProduct($item);
            $options = $this->optionsManagement->getOptions($item);
            /**
             * @var RequisitionListItemOptions $requisitionListItemOptions
             */
            $requisitionListItemOptions = $this->requisitionListOptionsItemFactory->create();
            $requisitionListItemOptions->setData(RequisitionListItemOptions::PRODUCT, $product);
            $requisitionListItemOptions->setData(RequisitionListItemOptions::OPTIONS, $options);
            $this->requisitionListItemOptions[$itemId] = $requisitionListItemOptions;
        }

        return $this->requisitionListItemOptions[$itemId];
    }
}
