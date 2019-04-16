<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Api\Plugin;

use Magento\CatalogStaging\Api\Plugin\ProductCustomOptionRepository;
use PHPUnit\Framework\TestCase;

class ProductCustomOptionRepositoryTest extends TestCase
{
    /**
     * @var \Magento\CatalogStaging\Api\Plugin\ProductCustomOptionRepository
     */
    private $plugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customOptionRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $optionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    protected function setUp()
    {
        $this->customOptionRepositoryMock =
            $this->getMockBuilder(\Magento\Catalog\Api\ProductCustomOptionRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->optionMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->plugin = new ProductCustomOptionRepository(
            $this->productRepositoryMock
        );
    }

    public function testBeforeSaveForNewUpdateWithCustomOption()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $this->optionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(null);
        $this->optionMock->expects($this->once())->method('setOptionId')->with(null);
        $this->optionMock->expects($this->never())->method('setData');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithCustomOption()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $this->optionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(2);
        $this->optionMock->expects($this->never())->method('setOptionId');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForNewOption()
    {
        $productSku = 'product_sku';
        $this->optionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->once())->method('getOptionId')->willReturn(null);
        $this->optionMock->expects($this->never())->method('setOptionId');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithCustomOptionAndValues()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $value = [
            'option_type_id' => 8,
            'price' => 10
        ];
        $newValue = [
            'option_type_id' => null,
            'price' => 10
        ];
        $this->optionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(null);
        $this->optionMock->expects($this->once())->method('setOptionId')->with(null);
        $this->optionMock->expects($this->exactly(2))
            ->method('getData')
            ->with('values', null)
            ->willReturn([$value]);
        $this->optionMock->expects($this->once())->method('setData')->with('values', [$newValue]);
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }
}
