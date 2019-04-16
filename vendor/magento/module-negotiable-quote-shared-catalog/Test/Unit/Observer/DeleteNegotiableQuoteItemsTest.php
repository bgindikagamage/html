<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Observer;

/**
 * Unit test for DeleteNegotiableQuoteItems observer.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteNegotiableQuoteItemsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogModuleConfig;

    /**
     * @var \Magento\NegotiableQuote\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteModuleConfig;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteManagement;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete|
     * \PHPUnit_Framework_MockObject_MockObject
     */
    private $itemDeleter;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $observer;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Observer\DeleteNegotiableQuoteItems
     */
    private $observerConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();
        $this->sharedCatalogModuleConfig = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagement =
            $this->getMockBuilder(\Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement::class)
            ->setMethods([])
                ->disableOriginalConstructor()
                ->getMock();
        $this->negotiableQuoteModuleConfig = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Config::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productItemRepository =
            $this->getMockBuilder(\Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class)
                ->setMethods([])
                ->getMockForAbstractClass();
        $this->productCollectionFactory =
            $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
            ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->itemDeleter =
            $this->getMockBuilder(\Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete::class)
                ->setMethods([])
                ->disableOriginalConstructor()
                ->getMock();

        $this->observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getWebsite'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer->expects($this->any())->method('getEvent')
            ->willReturn($this->event);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->observerConfig = $objectManager->getObject(
            \Magento\NegotiableQuoteSharedCatalog\Observer\DeleteNegotiableQuoteItems::class,
            [
                'storeManager' => $this->storeManager,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'quoteManagement' => $this->quoteManagement,
                'negotiableQuoteModuleConfig' => $this->negotiableQuoteModuleConfig,
                'productItemRepository' => $this->productItemRepository,
                'productCollectionFactory' => $this->productCollectionFactory,
                'sharedCatalogModuleConfig' => $this->sharedCatalogModuleConfig,
                'itemDeleter' => $this->itemDeleter,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->event->expects($this->atLeastOnce())->method('getWebsite')->willReturn(1);

        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->getMockForAbstractClass();

        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $website->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $this->negotiableQuoteModuleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->sharedCatalogModuleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->sharedCatalogModuleConfig->expects($this->atLeastOnce())
            ->method('getActiveSharedCatalogStoreIds')->willReturn([2]);

        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $searchResult =
            $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface::class)
                ->getMockForAbstractClass();
        $this->productItemRepository->expects($this->atLeastOnce())->method('getList')->willReturn($searchResult);
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->setMethods(['getSku', 'getCustomerGroupId'])
            ->getMockForAbstractClass();
        $productItem->expects($this->atLeastOnce())->method('getSku')->willReturn('sku');
        $productItem->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn(3);
        $searchResult->expects($this->atLeastOnce())->method('getItems')->willReturn([$productItem]);
        $searchResult->expects($this->atLeastOnce())->method('getTotalCount')->willReturn(1);

        $productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->productCollectionFactory->expects($this->once())->method('create')->willReturn($productCollection);
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getEntityId'])
            ->getMockForAbstractClass();
        $productCollection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $product->expects($this->atLeastOnce())->method('getEntityId')->willReturn(2);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn('sku');

        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->quoteManagement->expects($this->atLeastOnce())->method('retrieveQuoteItems')
            ->with(3, [2], [2], false)->willReturn([$quoteItem]);

        $this->observerConfig->execute($this->observer);
    }
}
