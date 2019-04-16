<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Plugin;

use \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete;

/**
 * Unit test for DeleteUnavailableNegotiableQuoteItems plugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteUnavailableNegotiableQuoteItemsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRetriever;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteManagement;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogProductsLoader;

    /**
     * @var Delete|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemDeleter;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Plugin\DeleteUnavailableNegotiableQuoteItems
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->productRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogRetriever = $this->getMockBuilder(
            \Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagement = $this->getMockBuilder(
            \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductsLoader = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\SharedCatalogProductsLoader::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->itemDeleter = $this->getMockBuilder(
            \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\NegotiableQuoteSharedCatalog\Plugin\DeleteUnavailableNegotiableQuoteItems::class,
            [
                'productRepository' => $this->productRepository,
                'productCollectionFactory' => $this->productCollectionFactory,
                'sharedCatalogRetriever' => $this->sharedCatalogRetriever,
                'quoteManagement' => $this->quoteManagement,
                'sharedCatalogProductsLoader' => $this->sharedCatalogProductsLoader,
                'itemDeleter' => $this->itemDeleter,
                'config' => $this->config,
            ]
        );
    }

    /**
     * Test for afterDelete method.
     *
     * @return void
     */
    public function testAfterDelete()
    {
        $productSku = 'SKU1';
        $customerGroupId = 1;
        $productId = 4;
        $storeIds = [2, 3];
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()->getMock();
        $productItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $productItem->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->productRepository->expects($this->once())->method('get')->with($productSku)->willReturn($product);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($productId);
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->config->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn($storeIds);
        $this->quoteManagement
            ->expects($this->once())
            ->method('retrieveQuoteItems')
            ->with($customerGroupId, [$productId], $storeIds)
            ->willReturn([$quoteItem]);
        $this->itemDeleter->expects($this->once())->method('deleteItems')->with([$quoteItem]);

        $productItemRepository = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->assertTrue($this->plugin->afterDelete($productItemRepository, true, $productItem));
    }

    /**
     * Test for afterDelete method with exception.
     *
     * @return void
     */
    public function testAfterDeleteWithException()
    {
        $productSku = 'SKU1';
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()->getMock();
        $productItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->productRepository->expects($this->once())->method('get')->with($productSku)->willThrowException(
            new \Magento\Framework\Exception\NoSuchEntityException()
        );
        $productItemRepository = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->assertTrue($this->plugin->afterDelete($productItemRepository, true, $productItem));
    }

    /**
     * Test for afterDeleteItems method.
     *
     * @return void
     */
    public function testAfterDeleteItems()
    {
        $productSku = 'SKU1';
        $customerGroupId = 1;
        $productId = 4;
        $storeIds = [2, 3];
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()->getMock();
        $productItem->expects($this->once())->method('getSku')->willReturn($productSku);
        $productItem->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->setMethods(['addFieldToFilter', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getEntityId'])
            ->getMockForAbstractClass();
        $this->productCollectionFactory->expects($this->once())->method('create')->willReturn($productCollection);
        $productCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('sku', ['in' => [$productSku]])
            ->willReturnSelf();
        $productCollection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $product->expects($this->atLeastOnce())->method('getEntityId')->willReturn($productId);
        $publicCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRetriever->expects($this->once())
            ->method('isSharedCatalogPresent')
            ->with($customerGroupId)
            ->willReturn(false);
        $this->sharedCatalogRetriever->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCatalogProductsLoader
            ->expects($this->atLeastOnce())
            ->method('getAssignedProductsSkus')
            ->with(2)
            ->willReturn(['SKU2']);
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->config->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn($storeIds);
        $this->quoteManagement
            ->expects($this->once())
            ->method('retrieveQuoteItems')
            ->with($customerGroupId, [$productId], $storeIds)
            ->willReturn([$quoteItem]);
        $this->itemDeleter->expects($this->once())->method('deleteItems')->with([$quoteItem]);
        $productItemRepository = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->assertTrue(
            $this->plugin->afterDeleteItems($productItemRepository, true, [$productItem])
        );
    }
}
