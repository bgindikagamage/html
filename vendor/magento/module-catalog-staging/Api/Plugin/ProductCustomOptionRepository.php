<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Api\Plugin;

/**
 * Plugin to support if exists option in the scope of current scheduled update
 */
class ProductCustomOptionRepository
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * ProductCustomOptionRepository constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * Verify if option exists in the scope of update.
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        $product = $this->productRepository->get($option->getProductSku());
        if ($option->getOptionId() !== null && $product->getOptionById($option->getOptionId()) === null) {
            $option->setOptionId(null);
            if (!empty($option->getData('values'))) {
                $existingValues = $option->getData('values');
                $newValues = [];
                foreach ($existingValues as $value) {
                    $value['option_type_id'] = null;
                    $newValues[] = $value;
                }
                $option->setData('values', $newValues);
            }
        }
    }
}
