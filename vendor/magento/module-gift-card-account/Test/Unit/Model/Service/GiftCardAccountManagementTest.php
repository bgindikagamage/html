<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\GiftCardAccount\Test\Unit\Model\Service;

use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardAccount;

class GiftCardAccountManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCardAccount\Model\Service\GiftCardAccountManagement
     */
    protected $service;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCardHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCard;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCardAccountFactory;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->giftCardHelperMock = $this->createMock(\Magento\GiftCardAccount\Helper\Data::class);
        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getGiftCardsAmount',
                'getBaseGiftCardsAmount',
                'getGiftCardsAmountUsed',
                'getBaseGiftCardsAmountUsed',
                '__wakeup',
                'getItemsCount'
            ]
        );
        $this->giftCard = $this->createMock(\Magento\GiftCardAccount\Model\Giftcardaccount::class);
        $this->giftCardAccountFactory = $this->getMockBuilder(
            \Magento\GiftCardAccount\Model\GiftcardaccountFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->service = $objectManager->getObject(
            \Magento\GiftCardAccount\Model\Service\GiftCardAccountManagement::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'giftCardHelper' => $this->giftCardHelperMock,
                'giftCardAccountFactory' => $this->giftCardAccountFactory
            ]
        );
    }

    public function testGetList()
    {
        $cartId = 12;

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->will($this->returnValue($this->quoteMock));
        $this->giftCardHelperMock
            ->expects($this->once())
            ->method('getCards')
            ->with($this->quoteMock)
            ->will($this->returnValue([['c' => 'ABC-123'], ['c' => 'DEF-098']]));
        $data = [
            GiftCardAccount::GIFT_CARDS => ['ABC-123', 'DEF-098'],
            GiftCardAccount::GIFT_CARDS_AMOUNT => 100,
            GiftCardAccount::BASE_GIFT_CARDS_AMOUNT => 90,
            GiftCardAccount::GIFT_CARDS_AMOUNT_USED => 50,
            GiftCardAccount::BASE_GIFT_CARDS_AMOUNT_USED => 40,
        ];
        $this->quoteMock->expects($this->once())->method('getGiftCardsAmount')->will($this->returnValue(100));
        $this->quoteMock->expects($this->once())->method('getBaseGiftCardsAmount')->will($this->returnValue(90));
        $this->quoteMock->expects($this->once())->method('getGiftCardsAmountUsed')->will($this->returnValue(50));
        $this->quoteMock->expects($this->once())->method('getBaseGiftCardsAmountUsed')->will($this->returnValue(40));

        $model = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardAccountFactory->expects($this->once())
            ->method('create')
            ->with(['data' => $data])
            ->willReturn($model);
        $this->assertInstanceOf(
             \Magento\GiftCardAccount\Model\Giftcardaccount::class,
            $this->service->getListByQuoteId($cartId)
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Cart 12 doesn't contain products
     */
    public function testSaveWithNoSuchEntityException()
    {
        $quoteId = 12;

        $dataObject = $this->getMockBuilder(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(0));
        $this->giftCard->expects($this->never())->method('getGiftCards');

        $this->service->saveByQuoteId($quoteId, $dataObject);
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage Could not add gift card code
     */
    public function testSaveWithCouldNotSaveException()
    {
        $quoteId = 12;
        $cardCode = [['ABC-123']];

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));

        $dataObject = $this->getMockBuilder(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->once())
            ->method('getGiftCards')->will($this->returnValue($cardCode));

        $dataObjectCode = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardAccountFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataObjectCode);

        $dataObjectCode
            ->expects($this->once())
            ->method('loadByCode')
            ->with(array_shift($cardCode))
            ->will($this->returnValue($this->giftCard));
        $exception = new \Magento\Framework\Exception\CouldNotSaveException(__('Could not add gift card code'));
        $dataObjectCode
            ->expects($this->any())
            ->method('addToCart')
            ->with(true, $this->quoteMock)
            ->will($this->throwException($exception));

        $this->service->saveByQuoteId($quoteId, $dataObject);
    }

    public function testSave()
    {
        $quoteId = 12;
        $cardCode = [['ABC-123']];

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $dataObject = $this->getMockBuilder(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->once())
            ->method('getGiftCards')->will($this->returnValue($cardCode));
        $dataObjectCode = $this->getMockBuilder(\Magento\GiftCardAccount\Model\Giftcardaccount::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardAccountFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataObjectCode);
        $dataObjectCode
            ->expects($this->once())
            ->method('loadByCode')
            ->with(array_shift($cardCode))
            ->willReturnSelf();
        $dataObjectCode
            ->expects($this->any())
            ->method('addToCart')
            ->with(true, $this->quoteMock)
            ->willReturnSelf();

        $this->assertTrue($this->service->saveByQuoteId($quoteId, $dataObject));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Cart 12 doesn't contain products
     */
    public function testDeleteWithNoSuchEntityException()
    {
        $cartId = 12;
        $couponCode = 'ABC-1223';

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(0));

        $this->service->deleteByQuoteId($cartId, $couponCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     * @expectedExceptionMessage Could not delete gift card from quote
     */
    public function testDeleteWithCouldNotDeleteException()
    {
        $cartId = 12;
        $couponCode = 'ABC-1223';

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->giftCardAccountFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->giftCard);
        $this->giftCard
            ->expects($this->once())
            ->method('loadByCode')
            ->with($couponCode)
            ->willReturnSelf();
        $exception = new \Magento\Framework\Exception\CouldNotDeleteException(
            __('Could not delete gift card from quote')
        );
        $this->giftCard
            ->expects($this->any())
            ->method('removeFromCart')
            ->with(true, $this->quoteMock)
            ->will($this->throwException($exception));

        $this->service->deleteByQuoteId($cartId, $couponCode);
    }

    public function testDelete()
    {
        $cartId = 12;
        $couponCode = 'ABC-1223';

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->giftCardAccountFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->giftCard);
        $this->giftCard
            ->expects($this->once())
            ->method('loadByCode')
            ->with($couponCode)
            ->will($this->returnValue($this->giftCard));
        $this->giftCard
            ->expects($this->any())
            ->method('removeFromCart')
            ->with(true, $this->quoteMock);

        $this->assertTrue($this->service->deleteByQuoteId($cartId, $couponCode));
    }
}
