<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Model\Price;

/**
 * Test for model ProductTierPriceLoader.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTierPriceLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeCurrency;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupRepository;

    /**
     * @var \Magento\Catalog\Api\TierPriceStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tierPriceStorage;

    /**
     * @var \Magento\SharedCatalog\Model\ProductItemTierPriceValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Model\Price\ProductTierPriceLoader
     */
    private $model;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->localeCurrency = $this->getMockBuilder(\Magento\Framework\Locale\CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage = $this->getMockBuilder(\Magento\Catalog\Api\TierPriceStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productItemTierPriceValidator = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ProductItemTierPriceValidator::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository = $this->getMockBuilder(
            \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\Price\ProductTierPriceLoader::class,
            [
                'storeManager' => $this->storeManager,
                'localeCurrency' => $this->localeCurrency,
                'customerGroupRepository' => $this->customerGroupRepository,
                'tierPriceStorage' => $this->tierPriceStorage,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'batchSize' => 1,
                'storageBatchSize' => 2,
            ]
        );
    }

    /**
     * Test populateProductTierPrices method.
     *
     * @param string $priceType
     * @param array $price
     * @return void
     * @dataProvider populateProductTierPricesDataProvider
     */
    public function testPopulateProductTierPrices($priceType, array $price)
    {
        $skus = ['test_sku1', 'test_sku2', 'test_sku3'];
        $storeId = 5;
        $customerGroupId = 13;
        $websiteId = 3;
        $sharedCatalogId = 1;
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storage = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\Form\Storage\Wizard::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())
            ->method('isTierPriceApplicable')
            ->willReturn(true);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with(1)->willReturn($sharedCatalog);
        $product->expects($this->atLeastOnce())->method('getSku')
            ->willReturnOnConsecutiveCalls($skus[0], $skus[1], $skus[2]);
        $this->tierPriceStorage->expects($this->exactly(3))->method('get')
            ->withConsecutive([[$skus[0]]], [[$skus[1]]], [[$skus[2]]])->willReturn([$tierPrice]);
        $sharedCatalog->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->customerGroupRepository->expects($this->once())
            ->method('getById')
            ->with($customerGroupId)
            ->willReturn($customerGroup);
        $tierPrice->expects($this->atLeastOnce())->method('getCustomerGroup')->willReturn('Retailer');
        $customerGroup->expects($this->atLeastOnce())->method('getCode')->willReturn('Retailer');
        $tierPrice->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $tierPrice->expects($this->atLeastOnce())->method('getQuantity')->willReturn(1);
        $tierPrice->expects($this->atLeastOnce())->method('getPriceType')->willReturn($priceType);
        $tierPrice->expects($this->atLeastOnce())->method('getPrice')->willReturn(12);
        if ($priceType == 'fixed') {
            $currency = $this->getMockBuilder(
                \Magento\Framework\Currency::class
            )
                ->disableOriginalConstructor()
                ->getMock();
            $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
            $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
            $this->localeCurrency->expects($this->atLeastOnce())
                ->method('getCurrency')
                ->with('USD')
                ->willReturn($currency);
            $currency->expects($this->atLeastOnce())
                ->method('toCurrency')
                ->with(12, ['display' => \Magento\Framework\Currency::NO_SYMBOL])
                ->willReturn('12');
        }
        $tierPrice->expects($this->atLeastOnce())->method('getSku')
            ->willReturnOnConsecutiveCalls($skus[0], $skus[1], $skus[2]);
        $storage->expects($this->exactly(2))
            ->method('setTierPrices')
            ->withConsecutive([[$skus[0] => [$price], $skus[1] => [$price]]], [[$skus[2] => [$price]]]);
        $this->model->populateProductTierPrices([$product, $product, $product], $sharedCatalogId, $storage);
    }

    /**
     * Data provider for populateProductTierPrices method.
     *
     * @return array
     */
    public function populateProductTierPricesDataProvider()
    {
        return [
          [
              'fixed',
              [
                  'qty' => 1,
                  'website_id' => 3,
                  'value_type' => \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_FIXED,
                  'price' => '12',
              ]
          ],
            [
                'percent',
                [
                    'qty' => 1,
                    'website_id' => 3,
                    'value_type' => \Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_DISCOUNT,
                    'percentage_value' => 12,
                ]
            ],
        ];
    }
}
