<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

/**
 * Unit test for TierPriceManagement model.
 */
class TierPriceManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Api\TierPriceStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tierPriceStorage;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupRepository;

    /**
     * @var \Magento\Catalog\Api\Data\TierPriceInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tierPriceFactory;

    /**
     * @var \Magento\SharedCatalog\Model\TierPriceManagement
     */
    private $tierPriceManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->tierPriceFactory = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerGroupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage = $this->getMockBuilder(\Magento\Catalog\Api\TierPriceStorageInterface::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->tierPriceManagement = $objectManager->getObject(
            \Magento\SharedCatalog\Model\TierPriceManagement::class,
            [
                'tierPriceStorage' => $this->tierPriceStorage,
                'customerGroupRepository' => $this->customerGroupRepository,
                'tierPriceFactory' => $this->tierPriceFactory,
                'batchSize' => 1,
            ]
        );
    }

    /**
     * Test for deleteProductTierPrices method.
     *
     * @return void
     */
    public function testDeleteProductTierPrices()
    {
        $customerGroupId = 1;
        $customerGroupCode = 'general';
        $productSkus = ['SKU1', 'SKU2'];
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->once())->method('getType')
            ->willReturn(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC);
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupRepository->expects($this->once())
            ->method('getById')->with($customerGroupId)->willReturn($customerGroup);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($customerGroupCode);
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->exactly(2))->method('get')
            ->withConsecutive([[$productSkus[0]]], [[$productSkus[1]]])->willReturn([$tierPrice]);
        $tierPrice->expects($this->exactly(2))->method('getQuantity')->willReturn(1);
        $tierPrice->expects($this->exactly(2))->method('getCustomerGroup')->willReturn($customerGroupCode);
        $priceUpdateResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->exactly(2))
            ->method('delete')->with([$tierPrice])->willReturn($priceUpdateResult);
        $this->tierPriceManagement->deleteProductTierPrices($sharedCatalog, $productSkus, true);
    }

    /**
     * Test for updateProductTierPrices method.
     *
     * @return void
     */
    public function testUpdateProductTierPrices()
    {
        $customerGroupId = 1;
        $customerGroupCode = 'general';
        $notLoggedInGroupCode = 'not_logged_in';
        $tierPricesData = [
            [
                'qty' => 1,
                'website_id' => 2,
                'percentage_value' => 30,
            ],
            [
                'qty' => 3,
                'website_id' => 4,
                'price' => 15,
            ],
        ];
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMock();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')
            ->willReturn(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC);
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupRepository->expects($this->exactly(2))->method('getById')
            ->withConsecutive([$customerGroupId], [0])->willReturn($customerGroup);
        $customerGroup->expects($this->exactly(2))->method('getCode')
            ->willReturnOnConsecutiveCalls($customerGroupCode, $notLoggedInGroupCode);
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceFactory->expects($this->exactly(2))->method('create')->willReturn($tierPrice);
        $tierPrice->expects($this->exactly(4))->method('setCustomerGroup')
            ->withConsecutive(
                [$customerGroupCode],
                [$notLoggedInGroupCode],
                [$customerGroupCode],
                [$notLoggedInGroupCode]
            )->willReturnSelf();
        $tierPrice->expects($this->exactly(2))->method('setQuantity')
            ->withConsecutive([$tierPricesData[0]['qty']], [$tierPricesData[1]['qty']])->willReturnSelf();
        $tierPrice->expects($this->exactly(2))->method('setWebsiteId')
            ->withConsecutive([$tierPricesData[0]['website_id']], [$tierPricesData[1]['website_id']])
            ->willReturnSelf();
        $tierPrice->expects($this->exactly(2))->method('setPriceType')
            ->withConsecutive(
                [\Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_DISCOUNT],
                [\Magento\Catalog\Api\Data\TierPriceInterface::PRICE_TYPE_FIXED]
            )->willReturnSelf();
        $tierPrice->expects($this->exactly(2))->method('setPrice')
            ->withConsecutive([$tierPricesData[0]['percentage_value']], [$tierPricesData[1]['price']])
            ->willReturnSelf();
        $priceUpdateResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->once())->method('update')
            ->with([$tierPrice, $tierPrice, $tierPrice, $tierPrice])->willReturn($priceUpdateResult);
        $this->tierPriceManagement->updateProductTierPrices($sharedCatalog, $tierPricesData);
    }

    /**
     * Test for deletePublicTierPrices method.
     *
     * @return void
     */
    public function testDeletePublicTierPrices()
    {
        $customerGroupCode = 'not_logged_in';
        $productSkus = ['SKU1', 'SKU2'];
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupRepository->expects($this->once())->method('getById')->with(0)->willReturn($customerGroup);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($customerGroupCode);
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->once())->method('get')->with($productSkus)->willReturn([$tierPrice]);
        $tierPrice->expects($this->once())->method('getCustomerGroup')->willReturn($customerGroupCode);
        $priceUpdateResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->once())
            ->method('delete')->with([$tierPrice])->willReturn($priceUpdateResult);
        $this->tierPriceManagement->deletePublicTierPrices($productSkus);
    }

    /**
     * Test for getItemPrices method.
     *
     * @return void
     */
    public function testGetItemPrices()
    {
        $customerGroupId = 1;
        $customerGroupCode = 'general';
        $productSkus = ['SKU1', 'SKU2'];
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupRepository->expects($this->once())
            ->method('getById')->with($customerGroupId)->willReturn($customerGroup);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($customerGroupCode);
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->once())
            ->method('get')->with($productSkus)->willReturn([$tierPrice, $tierPrice]);
        $tierPrice->expects($this->exactly(2))->method('getCustomerGroup')
            ->willReturnOnConsecutiveCalls($customerGroupCode, 'custom_group');
        $tierPrice->expects($this->exactly(2))->method('getQuantity')->willReturn(1);
        $this->assertEquals([$tierPrice], $this->tierPriceManagement->getItemPrices($customerGroupId, $productSkus));
    }

    /**
     * Test for addPricesForPublicCatalog method.
     *
     * @return void
     */
    public function testAddPricesForPublicCatalog()
    {
        $customerGroupId = 1;
        $customerGroupCode = 'general';
        $notLoggedInGroupCode = 'not_logged_in';
        $productSkus = ['SKU1', 'SKU2'];
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupRepository->expects($this->exactly(2))->method('getById')
            ->withConsecutive([$customerGroupId], [0])->willReturn($customerGroup);
        $customerGroup->expects($this->exactly(2))->method('getCode')
            ->willReturnOnConsecutiveCalls($customerGroupCode, $notLoggedInGroupCode);
        $tierPrice = $this->getMockBuilder(\Magento\Catalog\Api\Data\TierPriceInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->exactly(2))->method('get')
            ->withConsecutive([[$productSkus[0]]], [[$productSkus[1]]])
            ->willReturnOnConsecutiveCalls([$tierPrice, $tierPrice], []);
        $tierPrice->expects($this->exactly(2))->method('getCustomerGroup')
            ->willReturnOnConsecutiveCalls($customerGroupCode, 'custom_group');
        $tierPrice->expects($this->exactly(2))->method('getQuantity')->willReturn(1);
        $tierPrice->expects($this->once())->method('setCustomerGroup')->with($notLoggedInGroupCode)->willReturnSelf();
        $priceUpdateResult = $this->getMockBuilder(\Magento\Catalog\Api\Data\PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->tierPriceStorage->expects($this->once())
            ->method('update')->with([$tierPrice])->willReturn($priceUpdateResult);
        $this->tierPriceManagement->addPricesForPublicCatalog($customerGroupId, $productSkus);
    }
}
