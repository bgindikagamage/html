<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Test\Unit\Helper;

/**
 * Unit test for Magento\NegotiableQuote\Helper\Quote class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stockRepository;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restriction;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyManagement;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $snapshotQuote;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var \Magento\Company\Api\AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authorization;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userContext;

    /**
     * @var \Magento\NegotiableQuote\Model\PriceFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceFormatter;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $helper;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->stockRepository = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement = $this->getMockBuilder(\Magento\Company\Api\CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMockForAbstractClass();
        $this->snapshotQuote = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this->getMockBuilder(
            \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authorization = $this->getMockBuilder(\Magento\Company\Api\AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext = $this->getMockBuilder(\Magento\Authorization\Model\UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceFormatter = $this->getMockBuilder(\Magento\NegotiableQuote\Model\PriceFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->helper = $objectManagerHelper->getObject(
            \Magento\NegotiableQuote\Helper\Quote::class,
            [
                'stockRepository' => $this->stockRepository,
                'restriction' => $this->restriction,
                'companyManagement' => $this->companyManagement,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'authorization' => $this->authorization,
                'userContext' => $this->userContext,
                'priceFormatter' => $this->priceFormatter,
                '_request' => $this->request,
            ]
        );
    }

    /**
     * Test resolveCurrentQuote method if quote is not a snapshot.
     *
     * @return void
     */
    public function testResolveCurrentQuoteNotSnapshot()
    {
        $quoteId = 1;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($this->quote);

        $this->assertSame($this->quote, $this->helper->resolveCurrentQuote());
    }

    /**
     * Test resolveCurrentQuote method if quote doesn't exist.
     *
     * @return void
     */
    public function testResolveCurrentQuoteWithException()
    {
        $quoteId = 1;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->with($quoteId, ['*'])
            ->willThrowException($exception);

        $this->assertNull($this->helper->resolveCurrentQuote());
    }

    /**
     * Test resolveCurrentQuote method if quote is a snapshot.
     *
     * @return void
     */
    public function testResolveCurrentQuoteIsSnapshot()
    {
        $quoteId = 1;
        $extensionAttributes = $this->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($this->quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($this->quote)->willReturnSelf();
        $this->restriction->expects($this->once())->method('canSubmit')->willReturn(false);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getSnapshotQuote')
            ->with($quoteId)
            ->willReturn($this->snapshotQuote);

        $this->assertSame($this->snapshotQuote, $this->helper->resolveCurrentQuote(true));
    }

    /**
     * Test isEnabled method.
     *
     * @return void
     */
    public function testIsEnabled()
    {
        $userId = 1;
        $extensionAttributes = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteConfig'])
            ->getMockForAbstractClass();
        $quoteConfig = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($userId)
            ->willReturn($company);
        $company->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getQuoteConfig')->willReturn($quoteConfig);
        $quoteConfig->expects($this->atLeastOnce())->method('getIsQuoteEnabled')->willReturn(true);

        $this->assertTrue($this->helper->isEnabled());
    }

    /**
     * Test isEnabled method with exception.
     *
     * @return void
     */
    public function testIsEnabledWithException()
    {
        $userId = 1;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($userId)
            ->willThrowException($exception);

        $this->assertFalse($this->helper->isEnabled());
    }

    /**
     * Test getUserId method.
     *
     * @return void
     */
    public function testGetCurrentUserId()
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $this->assertSame($userId, $this->helper->getCurrentUserId());
    }

    /**
     * Test getSalesRepresentative method.
     *
     * @param bool $returnId
     * @param int|string $expectedResult
     * @param int $count
     * @return void
     * @dataProvider getSalesRepresentativeIdDataProvider
     */
    public function testGetSalesRepresentative($returnId, $expectedResult, $count)
    {
        $quoteId = 1;
        $customerId = 1;
        $salesRepId = 1;
        $customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($company);
        $company->expects($this->once())->method('getSalesRepresentativeId')->willReturn($salesRepId);
        $this->companyManagement->expects($this->exactly($count))
            ->method('getSalesRepresentative')
            ->with($salesRepId)
            ->willReturn('Sales Rep');

        $this->assertSame($expectedResult, $this->helper->getSalesRepresentative($quoteId, $returnId));
    }

    /**
     * Data provider for getSalesRepresentative method.
     *
     * @return array
     */
    public function getSalesRepresentativeIdDataProvider()
    {
        return [
            [true, 1, 0],
            [false, 'Sales Rep', 1]
        ];
    }

    /**
     * Test getSalesRepresentative method with exception.
     *
     * @return void
     */
    public function testGetSalesRepresentativeWithException()
    {
        $quoteId = 1;
        $customerId = 1;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willThrowException($exception);

        $this->assertFalse($this->helper->getSalesRepresentative($quoteId));
    }

    /**
     * Test formatPrice method.
     *
     * @return void
     */
    public function testFormatPrice()
    {
        $price = 100.0000;
        $code = 'USD';
        $this->priceFormatter->expects($this->once())
            ->method('formatPrice')
            ->with($price, $code)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->formatPrice($price, $code));
    }

    /**
     * Test isLockMessageDisplayed method.
     *
     * @param bool $isMessageDisplayed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isLockMessageDisplayedDataProvider
     */
    public function testIsLockMessageDisplayed($isMessageDisplayed, $expectedResult)
    {
        $this->restriction->expects($this->once())->method('isLockMessageDisplayed')->willReturn($isMessageDisplayed);

        $this->assertEquals($expectedResult, $this->helper->isLockMessageDisplayed());
    }

    /**
     * Data provider for isLockMessageDisplayed method.
     *
     * @return array
     */
    public function isLockMessageDisplayedDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * Test isExpiredMessageDisplayed method.
     *
     * @param bool $isMessageDisplayed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isExpiredMessageDisplayedDataProvider
     */
    public function testIsExpiredMessageDisplayed($isMessageDisplayed, $expectedResult)
    {
        $this->restriction->expects($this->once())
            ->method('isExpiredMessageDisplayed')
            ->willReturn($isMessageDisplayed);

        $this->assertEquals($expectedResult, $this->helper->isExpiredMessageDisplayed());
    }

    /**
     * Data provider for isExpiredMessageDisplayed method.
     *
     * @return array
     */
    public function isExpiredMessageDisplayedDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * Test getFormattedOriginalPrice method.
     *
     * @return void
     */
    public function testGetFormattedOriginalPrice()
    {
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedOriginalPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedOriginalPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test getFormattedCartPrice method.
     *
     * @return void
     */
    public function testGetFormattedCartPrice()
    {
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedCartPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedCartPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test retrieveCustomOptions method.
     *
     * @return void
     */
    public function testRetrieveCustomOptions()
    {
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBuyRequest'])
            ->getMockForAbstractClass();
        $buyRequest = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData', 'getData'])
            ->getMock();
        $options = [
            'super_attribute' => 'option_data',
            'options' => 'option_data',
            'bundle_option' => 'option_data',
            'custom_giftcard_amount' => 'option_data',
            'giftcard_amount' => 'option_data',
            'giftcard_message' => 'option_data',
            'giftcard_recipient_email' => 'option_data',
            'giftcard_recipient_name' => 'option_data',
            'giftcard_sender_email' => 'option_data',
            'giftcard_sender_name' => 'option_data'
        ];
        $item->expects($this->once())->method('getBuyRequest')->willReturn($buyRequest);
        $buyRequest->expects($this->atLeastOnce())->method('hasData')->willReturn(true);
        $buyRequest->expects($this->atLeastOnce())->method('getData')->willReturn('option_data');
        $result = http_build_query($options);

        $this->assertEquals($result, $this->helper->retrieveCustomOptions($item, true));
    }

    /**
     * Test getFormattedCatalogPrice method.
     *
     * @return void
     */
    public function testGetFormattedCatalogPrice()
    {
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedCatalogPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedCatalogPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test isSubmitAvailable method.
     *
     * @param bool $canSubmit
     * @param bool $expectedResult
     * @return void
     * @dataProvider isSubmitAvailableDataProvider
     */
    public function testIsSubmitAvailable($canSubmit, $expectedResult)
    {
        $this->restriction->expects($this->once())
            ->method('canSubmit')
            ->willReturn($canSubmit);

        $this->assertEquals($expectedResult, $this->helper->isSubmitAvailable());
    }

    /**
     * Data provider for isSubmitAvailable method.
     *
     * @return array
     */
    public function isSubmitAvailableDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * Test getStockForProduct method.
     *
     * @return void
     */
    public function testGetStockForProduct()
    {
        $productId = 1;
        $qty = 1;
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getId'])
            ->getMockForAbstractClass();
        $itemQuote = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentItemId', 'getProduct'])
            ->getMockForAbstractClass();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $stockItem = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getProductType')->willReturn('configurable');
        $item->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getAllItems')->willReturn([$itemQuote]);
        $itemQuote->expects($this->once())->method('getParentItemId')->willReturn(2);
        $item->expects($this->once())->method('getId')->willReturn(2);
        $itemQuote->expects($this->once())->method('getProduct')->willReturn($product);
        $product->expects($this->once())->method('getId')->willReturn($productId);
        $this->stockRepository->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItem);
        $stockItem->expects($this->once())->method('getQty')->willReturn($qty);

        $this->assertEquals(1, $this->helper->getStockForProduct($item));
    }

    /**
     * Test isAllowedManage method.
     *
     * @param bool $isAllowed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isAllowedManageDataProvider
     */
    public function testIsAllowedManage($isAllowed, $expectedResult)
    {
        $this->authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->helper->isAllowedManage());
    }

    /**
     * Data provider for isAllowedManage method.
     *
     * @return array
     */
    public function isAllowedManageDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * Test getItemTotal method.
     *
     * @return void
     */
    public function testGetItemTotal()
    {
        $item = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getItemTotal')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getItemTotal($item, $quoteCurrency, $baseCurrency));
    }
}
