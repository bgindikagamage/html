<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\AdvancedCheckout\Model;

use PHPUnit_Framework_MockObject_Matcher_InvokedCount;
use Magento\SharedCatalog\Model\SharedCatalogProductsLoader;

/**
 * Unit test for \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\BackendHideProductsAbsentInSharedCatalogPlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BackendHideProductsAbsentInSharedCatalogPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productLoaderMock;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \Magento\Backend\Model\Session\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionQuoteMock;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\BackendHideProductsAbsentInSharedCatalogPlugin
     */
    private $cartPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->productLoaderMock = $this->getMockBuilder(SharedCatalogProductsLoader::class)
            ->setMethods(['getAssignedProductsSkus'])
            ->disableOriginalConstructor()->getMock();
        $this->configMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()->getMock();
        $this->sessionQuoteMock = $this->getMockBuilder(\Magento\Backend\Model\Session\Quote::class)
            ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cartPlugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\BackendHideProductsAbsentInSharedCatalogPlugin::class,
            [
                'productLoader' => $this->productLoaderMock,
                'config' => $this->configMock,
                'sessionQuote' => $this->sessionQuoteMock,
                'storeManager' => $this->storeManager,
                'customerRepository' => $this->customerRepositoryMock
            ]
        );
    }

    /**
     * Test for afterCheckItem() method.
     *
     * @return void
     */
    public function testAfterCheckItem()
    {
        $groupId = 1;
        $customerId = 1;
        $skus = ['test_sku_1', 'test_sku_2'];
        $item = ['code' => 'test1', 'sku' => 'test_sku_3'];
        $result = ['code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU, 'sku' => 'test_sku_3'];

        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->configMock->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->sessionQuoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerRepositoryMock->expects($this->once())->method('getById')->with($customerId)
            ->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getGroupId')->willReturn($groupId);
        $this->productLoaderMock->expects($this->once())->method('getAssignedProductsSkus')->with($groupId)
            ->willReturn($skus);
        $cart = $this->getMockBuilder(\Magento\AdvancedCheckout\Model\Cart::class)
            ->disableOriginalConstructor()->getMock();
        $cart->expects($this->never())->method('getQuote');

        $this->assertEquals($result, $this->cartPlugin->afterCheckItem($cart, $item));
    }

    /**
     * Test for afterCheckItem() method when there is no customer ID in session.
     *
     * @return void
     */
    public function testAfterCheckItemIfNoCustomerInSession()
    {
        $groupId = 1;
        $skus = ['test_sku_1', 'test_sku_2'];
        $item = ['code' => 'test1', 'sku' => 'test_sku_3'];
        $result = ['code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU, 'sku' => 'test_sku_3'];
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->configMock->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->sessionQuoteMock->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->customerRepositoryMock->expects($this->never())->method('getById');
        $this->productLoaderMock->expects($this->once())->method('getAssignedProductsSkus')->with($groupId)
            ->willReturn($skus);
        $cart = $this->getMockBuilder(\Magento\AdvancedCheckout\Model\Cart::class)
            ->disableOriginalConstructor()->getMock();
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getCustomerGroupId')->willReturn($groupId);
        $cart->expects($this->once())->method('getQuote')->willReturn($quote);

        $this->assertEquals($result, $this->cartPlugin->afterCheckItem($cart, $item));
    }
}
