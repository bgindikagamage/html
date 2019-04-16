<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem\Validator;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit test for Stock.
 */
class StockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stockRegistryMock;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItemProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItem\Validator\Stock
     */
    private $stockValidator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->stockRegistryMock = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct = $this
            ->getMockBuilder(\Magento\RequisitionList\Model\RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->stockValidator = $objectManagerHelper->getObject(
            \Magento\RequisitionList\Model\RequisitionListItem\Validator\Stock::class,
            [
                'stockRegistry' => $this->stockRegistryMock,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
            ]
        );
    }

    /**
     * Test for validate method.
     *
     * @param bool $isInStock
     * @param float $stockQty
     * @param float $itemQty
     * @param int $getItemQtyInvokesCount
     * @param int $isProductCompositeInvokesCount
     * @param int $getStockItemQtyInvokesCount
     * @param bool $isValid
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        $isInStock,
        $stockQty,
        $itemQty,
        $getItemQtyInvokesCount,
        $isProductCompositeInvokesCount,
        $getStockItemQtyInvokesCount,
        $isValid
    ) {
        $itemMock = $this->getMockBuilder(\Magento\RequisitionList\Model\RequisitionListItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty'])
            ->getMock();
        $itemMock->expects($this->exactly($getItemQtyInvokesCount))
            ->method('getQty')
            ->willReturn($itemQty);
        $productMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isComposite'])
            ->getMockForAbstractClass();
        $productMock->expects($this->exactly($isProductCompositeInvokesCount))
            ->method('isComposite')
            ->willReturn(false);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')
            ->willReturn($productMock);

        $stockItemMock = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock->expects($this->atLeastOnce())
            ->method('getIsInStock')
            ->willReturn($isInStock);
        $stockItemMock->expects($this->exactly($getStockItemQtyInvokesCount))
            ->method('getQty')
            ->willReturn($stockQty);
        $this->stockRegistryMock->expects($this->atLeastOnce())
            ->method('getStockItem')
            ->willReturn($stockItemMock);
        $errors = $this->stockValidator->validate($itemMock);

        $this->assertEquals($isValid, empty($errors));
    }

    /**
     * Data provider for validate.
     *
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                false,
                0,
                1,
                0,
                0,
                0,
                false
            ],
            [
                true,
                10,
                11,
                1,
                1,
                1,
                false
            ],
            [
                true,
                11,
                10,
                1,
                0,
                1,
                true
            ]
        ];
    }
}
