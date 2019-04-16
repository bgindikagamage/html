<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for Cart class.
 * @magentoDataFixture Magento/Catalog/_files/multiple_products.php
 */
class CartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\QuickOrder\Model\Cart
     */
    private $cart;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->cart = Bootstrap::getObjectManager()->create(\Magento\QuickOrder\Model\Cart::class);
    }

    /**
     * Test for method CheckItem.
     *
     * @dataProvider checkItemDataProvider
     * @param array $passedData
     * @param array $expectedItem
     * @return void
     */
    public function testCheckItem($passedData, $expectedItem)
    {
        $this->cart->setContext(\Magento\AdvancedCheckout\Model\Cart::CONTEXT_FRONTEND);
        $result = $this->cart->checkItem($passedData['sku'], $passedData['qty']);
        foreach ($expectedItem as $itemKey => $itemValue) {
            $this->assertEquals($itemValue, $result[$itemKey]);
        }
    }

    /**
     * @return array
     */
    public function checkItemDataProvider()
    {
        return [
            [
                [
                    'sku' => 'simple1',
                    'qty' => ''
                ],
                [
                    'qty' => floatval(1),
                    'sku' => 'simple1',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS
                ]
            ],
            [
                [
                    'sku' => 'simple1',
                    'qty' => floatval(101)
                ],
                [
                    'qty' => floatval(101),
                    'sku' => 'simple1',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED
                ]
            ],
            [
                [
                    'sku' => 'simple3',
                    'qty' => ''
                ],
                [
                    'qty' => floatval(1),
                    'sku' => 'simple3',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU
                ]
            ],
            [
                [
                    'sku' => 'not_existing_product',
                    'qty' => ''
                ],
                [
                    'qty' => floatval(1),
                    'sku' => 'not_existing_product',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU
                ]
            ]
        ];
    }
}
