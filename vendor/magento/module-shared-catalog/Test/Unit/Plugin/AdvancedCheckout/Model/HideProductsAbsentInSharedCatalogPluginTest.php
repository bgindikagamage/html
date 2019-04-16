<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\AdvancedCheckout\Model;

use PHPUnit_Framework_MockObject_Matcher_InvokedCount;

/**
 * Unit test for \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin.
 */
class HideProductsAbsentInSharedCatalogPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productLoader;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin
     */
    private $cartPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->productLoader = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogProductsLoader::class)
            ->setMethods(['getAssignedProductsSkus'])
            ->disableOriginalConstructor()->getMock();

        $this->config = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cartPlugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin::class,
            [
                'productLoader' => $this->productLoader,
                'storeManager' => $this->storeManager,
                'config' => $this->config
            ]
        );
    }

    /**
     * Test for afterCheckItem().
     *
     * @param boolean $isActive
     * @param PHPUnit_Framework_MockObject_Matcher_InvokedCount $call
     * @param array $item
     * @param array $result
     * @return void
     * @dataProvider afterCheckItemDataProvider
     */
    public function testAfterCheckItem($isActive, $call, $item, $result)
    {
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->atLeastOnce())->method('isActive')->willReturn($isActive);

        $cart = $this->getMockBuilder(\Magento\AdvancedCheckout\Model\Cart::class)
            ->setMethods(['getActualQuote'])
            ->disableOriginalConstructor()->getMock();
        $customerGroupId = 99;
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($call)->method('getCustomerGroupId')->willReturn($customerGroupId);
        $skus = ['test_sku_1', 'test_sku_2'];
        $this->productLoader
            ->expects($call)
            ->method('getAssignedProductsSkus')
            ->with($customerGroupId)
            ->willReturn($skus);

        $cart->expects($call)->method('getActualQuote')->willReturn($quote);
        $this->assertEquals($result, $this->cartPlugin->afterCheckItem($cart, $item));
    }

    /**
     * Data provider for afterCheckItem() test.
     *
     * @return array
     */
    public function afterCheckItemDataProvider()
    {
        return [
            [false, $this->never(), ['code' => 'test_1', 'test_sku_1'], ['code' => 'test_1', 'test_sku_1']],
            [
                'true', $this->atLeastOnce(),
                ['sku' => 'test_sku_3'],
                ['code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU, 'sku' => 'test_sku_3']
            ]
        ];
    }
}
