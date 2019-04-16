<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Model;

/**
 * Unit test for QuoteManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cartRepository;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cartItemRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cartFactory;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemCollectionFactory;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->cartFactory = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(\Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->itemCollectionFactory = $this
            ->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->cartRepository = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->cartItemRepository = $this->getMockBuilder(\Magento\Quote\Api\CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->quoteManagement = $objectManager->getObject(
            \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'cartRepository' => $this->cartRepository,
                'cartItemRepository' => $this->cartItemRepository,
                'cartFactory' => $this->cartFactory,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'itemCollectionFactory' => $this->itemCollectionFactory,
            ]
        );
    }

    /**
     * Test for deleteItems method.
     *
     * @return void
     */
    public function testDeleteItems()
    {
        $customerGroupId = 1;
        $cartId = 2;
        $cartItemId = 3;
        $productSkus = ['SKU1', 'SKU2'];
        $storeIds = [1, 2];
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(
                ['customer_group_id', $customerGroupId],
                ['main_table.is_active', 1],
                ['store_id', $storeIds, 'in']
            )
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\CartSearchResultsInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->cartRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $cart = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->disableOriginalConstructor()->getMock();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$cart]);
        $cart->expects($this->atLeastOnce())->method('getId')->willReturn($cartId);
        $cartItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->withConsecutive(
                ['sku', ['in' => $productSkus]],
                ['quote_id', ['in' => [$cartId]]]
            )
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($cart);
        $quoteItemCollection->expects($this->once())->method('setQuote')->with($cart)->willReturnSelf();
        $quoteItemCollection->expects($this->once())->method('clear')->willReturnSelf();
        $quoteItemCollection->expects($this->once())->method('getItems')->willReturn([$cartItem]);
        $this->itemCollectionFactory->expects($this->once())->method('create')->willReturn($quoteItemCollection);
        $cartItem->expects($this->once())->method('getOrigData')->willReturn($cartId);
        $cartItem->expects($this->once())->method('getItemId')->willReturn($cartItemId);
        $this->cartItemRepository->expects($this->once())
            ->method('deleteById')->with($cartId, $cartItemId)->willReturn(true);
        $this->quoteManagement->deleteItems($productSkus, $customerGroupId, $storeIds);
    }

    /**
     * Test for retrieveQuoteItems method.
     *
     * @return void
     */
    public function testRetrieveQuoteItems()
    {
        $customerGroupId = 1;
        $quoteId = 2;
        $productId = 3;
        $storeIds = [1, 2];
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(['customer_group_id', $customerGroupId], ['store_id', $storeIds, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(\Magento\Framework\Api\SearchResultsInterface::class)
            ->disableOriginalConstructor()->getMock();
        $quote = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $quoteItemCollection = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->withConsecutive(
                ['product_id', ['in' => [$productId]]],
                ['quote_id', ['in' => [$quoteId]]],
                ['parent_item_id', ['null' => true]]
            )
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($quote);
        $quoteItemCollection->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItemCollection->expects($this->once())->method('clear')->willReturnSelf();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$quote]);
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection->expects($this->once())->method('getItems')->willReturn([$quoteItem]);
        $this->itemCollectionFactory->expects($this->once())->method('create')->willReturn($quoteItemCollection);
        $this->assertEquals(
            [$quoteItem],
            $this->quoteManagement->retrieveQuoteItems($customerGroupId, [$productId], $storeIds)
        );
    }

    /**
     * Test for retrieveQuoteItemsForCustomers method.
     *
     * @return void
     */
    public function testRetrieveQuoteItemsForCustomers()
    {
        $customerIds = [1];
        $quoteId = 2;
        $productId = 3;
        $storeIds = [1, 2];
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(['customer_id', $customerIds, 'in'], ['store_id', $storeIds, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(\Magento\Framework\Api\SearchResultsInterface::class)
            ->disableOriginalConstructor()->getMock();
        $quote = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $quoteItemCollection = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->withConsecutive(
                ['product_id', ['in' => [$productId]]],
                ['quote_id', ['in' => [$quoteId]]]
            )
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($quote);
        $quoteItemCollection->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItemCollection->expects($this->once())->method('clear')->willReturnSelf();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$quote]);
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemCollection->expects($this->once())->method('getItems')->willReturn([$quoteItem]);
        $this->itemCollectionFactory->expects($this->once())->method('create')->willReturn($quoteItemCollection);
        $this->assertEquals(
            [$quoteItem],
            $this->quoteManagement->retrieveQuoteItemsForCustomers($customerIds, [$productId], $storeIds)
        );
    }
}
