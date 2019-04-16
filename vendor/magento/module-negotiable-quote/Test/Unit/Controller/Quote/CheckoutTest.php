<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

/**
 * Class CheckoutTest.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\Checkout
     */
    private $controller;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\CheckoutQuoteValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutQuoteValidator;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourse;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRestriction;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resourse = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->quoteItemManagement =
            $this->createMock(\Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface::class);
        $redirectFactory =
            $this->createPartialMock(\Magento\Framework\Controller\Result\RedirectFactory::class, ['create']);
        $redirect = $this->createMock(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->expects($this->any())->method('setPath')->will($this->returnSelf());
        $redirectFactory->expects($this->any())->method('create')->will($this->returnValue($redirect));
        $this->resultJsonFactory =
            $this->createPartialMock(\Magento\Framework\Controller\Result\JsonFactory::class, ['create']);
        $this->quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->customerRestriction = $this->createMock(\Magento\NegotiableQuote\Model\Restriction\Customer::class);
        $this->quote = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\CartInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->quoteRepository->expects($this->atLeastOnce())->method('get')->will($this->returnValue($this->quote));
        $this->checkoutQuoteValidator = $this->createMock(\Magento\NegotiableQuote\Model\CheckoutQuoteValidator::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->controller = $objectManager->getObject(
            \Magento\NegotiableQuote\Controller\Quote\Checkout::class,
            [
                'resultJsonFactory' => $this->resultJsonFactory,
                'quoteRepository' => $this->quoteRepository,
                'customerRestriction' => $this->customerRestriction,
                'checkoutQuoteValidator' => $this->checkoutQuoteValidator,
                '_request' => $this->resourse,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $redirectFactory,
                'quoteItemManagement' => $this->quoteItemManagement,
            ]
        );
    }

    /**
     * Test of execute() method.
     *
     * @param int $invalidItemsQty
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($invalidItemsQty)
    {
        $this->resourse->expects($this->once())
            ->method('getParam')->with('quote_id')->will($this->returnValue(1));
        $this->checkoutQuoteValidator->expects($this->once())
            ->method('countInvalidQtyItems')
            ->with($this->quote)
            ->willReturn($invalidItemsQty);
        $this->customerRestriction->expects($this->once())->method('canSubmit')->willReturn(true);
        $quoteNegotiation = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $extensionAttributes = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote', 'setShippingAssignments']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->will($this->returnValue($quoteNegotiation));
        $this->quote->expects($this->any())->method('getExtensionAttributes')
            ->will($this->returnValue($extensionAttributes));
        $quoteNegotiation->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(null);
        $this->quoteItemManagement->expects($this->once())->method('recalculateOriginalPriceTax')->with(1);
        if ($invalidItemsQty > 0) {
            $message = __(
                '%1 products require your attention. Please contact the Seller if you have any questions.',
                $invalidItemsQty
            );
            $this->messageManager->expects($this->once())
                ->method('addError')
                ->with($message);
        }
        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Data Provider for testExecute().
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [0],
            [1]
        ];
    }
}
