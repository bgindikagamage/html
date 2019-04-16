<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Form\Storage;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;

/**
 * Mass assignment of products to a shared catalog.
 */
class SharedCatalogMassAssignment
{
    /**
     * @var \Magento\SharedCatalog\Model\Price\ProductTierPriceLoader
     */
    private $productTierPriceLoader;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogAssignment
     */
    private $sharedCatalogAssignment;

    /**
     * @param \Magento\SharedCatalog\Model\Price\ProductTierPriceLoader $productTierPriceLoader
     * @param \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment
     */
    public function __construct(
        \Magento\SharedCatalog\Model\Price\ProductTierPriceLoader $productTierPriceLoader,
        \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment
    ) {
        $this->productTierPriceLoader = $productTierPriceLoader;
        $this->sharedCatalogAssignment = $sharedCatalogAssignment;
    }

    /**
     * Mass assignment of products to a shared catalog.
     *
     * If $isAssign = true - adding products to a shared catalog
     * If $isAssign = false - removing products from a shared catalog
     * Populating storage with tier prices
     *
     * @param AbstractCollection $collection
     * @param \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage
     * @param int $sharedCatalogId
     * @param bool $isAssign
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function assign(
        AbstractCollection $collection,
        \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage,
        $sharedCatalogId,
        $isAssign
    ) {
        $skus = array_map(
            function ($product) {
                return $product->getSku();
            },
            $collection->getItems()
        );

        if ($isAssign) {
            $storage->assignProducts($skus);
            $categoryIds = $this->sharedCatalogAssignment->getAssignCategoryIdsByProductSkus($skus);
            $storage->assignCategories($categoryIds);
        } else {
            $storage->unassignProducts($skus);
        }

        $this->productTierPriceLoader->populateProductTierPrices(
            $collection->getItems(),
            $sharedCatalogId,
            $storage
        );
    }
}
