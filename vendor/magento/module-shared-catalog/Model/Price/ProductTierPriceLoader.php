<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Price;

use Magento\Store\Model\Store;

/**
 * Class for populating storage with tier prices of products assigned to shared catalog.
 */
class ProductTierPriceLoader
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $customerGroupRepository;

    /**
     * @var \Magento\Catalog\Api\TierPriceStorageInterface
     */
    private $tierPriceStorage;

    /**
     * @var \Magento\SharedCatalog\Model\ProductItemTierPriceValidator
     */
    private $productItemTierPriceValidator;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var int
     */
    private $storageBatchSize;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage
     * @param \Magento\SharedCatalog\Model\ProductItemTierPriceValidator $productItemTierPriceValidator
     * @param \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param int $batchSize [optional]
     * @param int $storageBatchSize [optional]
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository,
        \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage,
        \Magento\SharedCatalog\Model\ProductItemTierPriceValidator $productItemTierPriceValidator,
        \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository,
        $batchSize = 100,
        $storageBatchSize = 1000
    ) {
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->tierPriceStorage = $tierPriceStorage;
        $this->productItemTierPriceValidator = $productItemTierPriceValidator;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->batchSize = $batchSize;
        $this->storageBatchSize = $storageBatchSize;
    }

    /**
     * Check if tier price is applicable for products and populate storage with tier prices.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @param int $sharedCatalogId
     * @param \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *      If customer group linked to shared catalog doesn't exist
     */
    public function populateProductTierPrices(
        array $products,
        $sharedCatalogId,
        \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage
    ) {
        $productSkus = [];
        foreach ($products as $product) {
            if ($this->productItemTierPriceValidator->isTierPriceApplicable($product->getTypeId())) {
                $productSkus[] = $product->getSku();
            }
        }
        if (!empty($productSkus)) {
            $this->populateTierPrices(
                $productSkus,
                $sharedCatalogId,
                $storage
            );
        }
    }

    /**
     * Load products tier prices and populate storage with them.
     *
     * @param array $productSkus
     * @param int $sharedCatalogId
     * @param \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *      If shared catalog or customer group linked to shared catalog doesn't exist
     */
    public function populateTierPrices(
        array $productSkus,
        $sharedCatalogId,
        \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage
    ) {
        $pricesBySkus = [];
        $sharedCatalog = $this->sharedCatalogRepository->get($sharedCatalogId);
        $sharedCatalogStore = $this->storeManager->getStore($sharedCatalog->getStoreId());

        $customerGroup = $this->customerGroupRepository->getById($sharedCatalog->getCustomerGroupId());
        while (count($productSkus)) {
            $tierPrices = $this->tierPriceStorage->get(array_splice($productSkus, 0, $this->batchSize));
            foreach ($tierPrices as $tierPrice) {
                if ($tierPrice->getCustomerGroup() != $customerGroup->getCode()
                    || !$this->isWebsitePriceAllowed($tierPrice, $sharedCatalogStore->getWebsiteId())
                ) {
                    continue;
                }
                $price = [];
                $price['qty'] = (int)$tierPrice->getQuantity();
                $price['website_id'] = $tierPrice->getWebsiteId();
                if ($tierPrice->getPriceType() == \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_FIXED) {
                    $price['value_type'] = \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_FIXED;
                    $price['price'] = $this->formatPrice($tierPrice->getPrice());
                } else {
                    $price['value_type'] = \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_DISCOUNT;
                    $price['percentage_value'] = $tierPrice->getPrice();
                }
                $pricesBySkus[$tierPrice->getSku()][] = $price;
            }
            if (count($pricesBySkus) >= $this->storageBatchSize) {
                $storage->setTierPrices($pricesBySkus);
                $pricesBySkus = [];
            }
        }
        if (!empty($pricesBySkus)) {
            $storage->setTierPrices($pricesBySkus);
        }
    }

    /**
     * Check if tier price is allowed for the shared catalog website.
     *
     * @param \Magento\Catalog\Api\Data\TierPriceInterface $tierPrice
     * @param int $sharedCatalogWebsiteId
     * @return bool
     */
    private function isWebsitePriceAllowed(
        \Magento\Catalog\Api\Data\TierPriceInterface $tierPrice,
        $sharedCatalogWebsiteId
    ) {
        if ($sharedCatalogWebsiteId == Store::DEFAULT_STORE_ID) {
            return true;
        }
        if (!is_numeric($tierPrice->getWebsiteId())
            || ($tierPrice->getWebsiteId() != $sharedCatalogWebsiteId
                && $tierPrice->getWebsiteId() != Store::DEFAULT_STORE_ID)) {
            return false;
        }

        return true;
    }

    /**
     * Format price according to the locale of the currency.
     *
     * @param float $value
     * @return string
     */
    private function formatPrice($value)
    {
        $store = $this->storeManager->getStore();
        $currency = $this->localeCurrency->getCurrency($store->getBaseCurrencyCode());
        $value = $currency->toCurrency($value, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);

        return $value;
    }
}
