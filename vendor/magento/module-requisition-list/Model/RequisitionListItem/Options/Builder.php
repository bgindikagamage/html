<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model\RequisitionListItem\Options;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\OptionFactory;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Requisition List Item options builder.
 */
class Builder
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItem\OptionFactory
     */
    private $optionFactory;

    /**
     * @var \Magento\RequisitionList\Model\OptionsManagement
     */
    private $optionsManagement;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $infoBuyRequestOptionCode = 'info_buyRequest';

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param OptionFactory $optionFactory
     * @param OptionsManagement $optionsManagement
     * @param SerializerInterface $serializer
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        OptionFactory $optionFactory,
        OptionsManagement $optionsManagement,
        SerializerInterface $serializer
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->optionFactory = $optionFactory;
        $this->optionsManagement = $optionsManagement;
        $this->serializer = $serializer;
    }

    /**
     * Prepare options for the requisition list item.
     *
     * @param array $buyRequest
     * @param int $itemId
     * @param bool $allowMisconfiguredProducts
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buyRequest, $itemId, $allowMisconfiguredProducts)
    {
        $itemOptions = ['info_buyRequest' => $buyRequest];

        if (isset($buyRequest['product'])) {
            $productId = $buyRequest['product'];
        }

        if (!isset($productId)) {
            return $itemOptions;
        }

        $storeId = $this->storeManager->getStore()->getId();
        try {
            $product = $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cannot specify product.'));
        }

        $buyRequestData = new \Magento\Framework\DataObject($buyRequest);
        $cartCandidates = $product->getTypeInstance()->processConfiguration($buyRequestData, clone $product);

        if (is_string($cartCandidates)) {
            if ($allowMisconfiguredProducts) {
                return [];
            }
            throw new \Magento\Framework\Exception\LocalizedException(__($cartCandidates));
        }

        $cartCandidates = (array)$cartCandidates;
        $parentProduct = null;
        foreach ($cartCandidates as $candidate) {
            if ($candidate->getParentProductId()) {
                continue;
            }
            $parentProduct = $candidate;
        }

        $options = $this->retrieveItemOptions($itemId, $parentProduct);

        return $options;
    }

    /**
     * Retrieve requisition item options.
     *
     * @param int $itemId
     * @param ProductInterface $product
     * @return array
     */
    private function retrieveItemOptions($itemId, ProductInterface $product)
    {
        $productOptions = $product->getCustomOptions();

        foreach ($productOptions as $productOption) {
            $this->optionsManagement->addOption($productOption, $itemId);
        }

        $itemOptions = $this->optionsManagement->getOptionsByRequisitionListItemId($itemId);
        $options = [];

        foreach ($itemOptions as $code => $option) {
            $options[$code] = $option->getValue();
            if ($code === $this->infoBuyRequestOptionCode && is_string($option->getValue())) {
                $options[$code] = $this->serializer->unserialize($option->getValue());
            }
        }

        return $options;
    }
}
