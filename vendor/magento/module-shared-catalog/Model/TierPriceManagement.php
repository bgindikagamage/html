<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;

/**
 * Shared catalog prices actions.
 */
class TierPriceManagement
{
    /**
     * @var \Magento\Catalog\Api\TierPriceStorageInterface
     */
    private $tierPriceStorage;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $customerGroupRepository;

    /**
     * @var \Magento\Catalog\Api\Data\TierPriceInterfaceFactory
     */
    private $tierPriceFactory;

    /**
     * @var array
     */
    private $customerGroupCodeById = [];

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @param \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Catalog\Api\Data\TierPriceInterfaceFactory $tierPriceFactory
     * @param int $batchSize [optional]
     */
    public function __construct(
        \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage,
        \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository,
        \Magento\Catalog\Api\Data\TierPriceInterfaceFactory $tierPriceFactory,
        $batchSize = 100
    ) {
        $this->tierPriceStorage = $tierPriceStorage;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->batchSize = $batchSize;
    }

    /**
     * Delete tier prices by product SKUs for specified shared catalog.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @param array $skus
     * @param bool $defaultQtyOnly [optional]
     * @return void
     */
    public function deleteProductTierPrices(
        SharedCatalogInterface $sharedCatalog,
        array $skus,
        $defaultQtyOnly = false
    ) {
        $groupCodes[] = $this->getCustomerGroupCode($sharedCatalog->getCustomerGroupId());
        if ($sharedCatalog->getType() == SharedCatalogInterface::TYPE_PUBLIC) {
            $groupCodes[] = ProductItemManagementInterface::CUSTOMER_GROUP_NOT_LOGGED_IN;
        }

        while (count($skus)) {
            $tierPrices = [];
            $prices = $this->tierPriceStorage->get(array_splice($skus, 0, $this->batchSize));
            foreach ($prices as $price) {
                if ((!$defaultQtyOnly || $price->getQuantity() == ProductItemManagementInterface::DEFAULT_QTY)
                    && in_array($price->getCustomerGroup(), $groupCodes)
                ) {
                    $tierPrices[] = $price;
                }
            }
            if (!empty($tierPrices)) {
                $this->tierPriceStorage->delete($tierPrices);
            }
        }
    }

    /**
     * Prepare product tier prices.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @param array $tierPricesData
     * @return void
     */
    public function updateProductTierPrices(
        SharedCatalogInterface $sharedCatalog,
        array $tierPricesData
    ) {
        $tierPrices = [];
        $customerGroupCode = $this->getCustomerGroupCode($sharedCatalog->getCustomerGroupId());
        foreach ($tierPricesData as $tierPriceData) {
            /** @var TierPriceInterface $tierPrice */
            $tierPrice = $this->tierPriceFactory->create();
            $tierPrice->setCustomerGroup($customerGroupCode);
            $tierPrice->setQuantity($tierPriceData['qty']);
            $tierPrice->setWebsiteId($tierPriceData['website_id']);
            $tierPrice->setPriceType(
                !empty($tierPriceData['percentage_value'])
                ? \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_DISCOUNT
                : \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_FIXED
            );
            $tierPrice->setPrice(
                !empty($tierPriceData['percentage_value'])
                ? $tierPriceData['percentage_value']
                : $tierPriceData['price']
            );
            if ($sharedCatalog->getType() == SharedCatalogInterface::TYPE_PUBLIC) {
                $publicTierPrice = clone $tierPrice;
                $publicTierPrice->setCustomerGroup(
                    $this->getCustomerGroupCode(ProductItemManagementInterface::CUSTOMER_GROUP_NOT_LOGGED_IN)
                );
                $tierPrices[] = $publicTierPrice;
            }
            $tierPrices[] = $tierPrice;
        }

        if (!empty($tierPrices)) {
            $this->tierPriceStorage->update($tierPrices);
        }
    }

    /**
     * Remove public tier prices by product SKU.
     *
     * @param array $skus
     * @return void
     */
    public function deletePublicTierPrices(array $skus)
    {
        $customerGroupCode = $this->getCustomerGroupCode(ProductItemManagementInterface::CUSTOMER_GROUP_NOT_LOGGED_IN);
        $tierPrices = [];
        $prices = $this->tierPriceStorage->get($skus);
        foreach ($prices as $price) {
            if ($price->getCustomerGroup() == $customerGroupCode) {
                $tierPrices[] = $price;
            }
        }
        if (!empty($tierPrices)) {
            $this->tierPriceStorage->delete($tierPrices);
        }
    }

    /**
     * Get tier prices for specified customer group by product SKUs.
     *
     * @param int $customerGroupId
     * @param array $skus
     * @return TierPriceInterface[]
     */
    public function getItemPrices($customerGroupId, array $skus)
    {
        $groupPrices = [];
        $customerGroupCode = $this->getCustomerGroupCode($customerGroupId);
        $prices = $this->tierPriceStorage->get($skus);
        foreach ($prices as $price) {
            if ($price->getQuantity() == ProductItemManagementInterface::DEFAULT_QTY
                && $price->getCustomerGroup() == $customerGroupCode
            ) {
                $groupPrices[] = $price;
            }
        }

        return $groupPrices;
    }

    /**
     * Duplicate all tier prices for NOT_LOGGED_IN customer group.
     *
     * @param int $customerGroupId
     * @param array $skus
     * @return void
     */
    public function addPricesForPublicCatalog($customerGroupId, array $skus)
    {
        while (count($skus)) {
            $prices = $this->getItemPrices($customerGroupId, array_splice($skus, 0, $this->batchSize));
            if (!empty($prices)) {
                foreach ($prices as $price) {
                    $price->setCustomerGroup(
                        $this->getCustomerGroupCode(ProductItemManagementInterface::CUSTOMER_GROUP_NOT_LOGGED_IN)
                    );
                }
                $this->tierPriceStorage->update($prices);
            }
        }
    }

    /**
     * Get customer group code by id.
     *
     * @param int $customerGroupId
     * @return string
     */
    private function getCustomerGroupCode($customerGroupId)
    {
        if (!isset($this->customerGroupCodeById[$customerGroupId])) {
            $customerGroup = $this->customerGroupRepository->getById($customerGroupId);
            $this->customerGroupCodeById[$customerGroupId] = $customerGroup->getCode();
        }
        return $this->customerGroupCodeById[$customerGroupId];
    }
}
