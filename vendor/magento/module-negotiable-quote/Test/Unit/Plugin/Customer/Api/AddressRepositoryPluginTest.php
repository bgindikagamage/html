<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Customer\Api;

/**
 * Unit test for Magento\NegotiableQuote\Plugin\Customer\Api\AddressRepositoryPlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddressRepositoryPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteAddress;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \Magento\NegotiableQuote\Model\Customer\RecalculationStatus|\PHPUnit_Framework_MockObject_MockObject
     */
    private $recalculationStatus;

    /**
     * @var \Magento\NegotiableQuote\Plugin\Customer\Api\AddressRepositoryPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteAddress = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteRepository = $this->getMockBuilder(
            \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemManagement = $this->getMockBuilder(
            \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->recalculationStatus = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\Customer\RecalculationStatus::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\NegotiableQuote\Plugin\Customer\Api\AddressRepositoryPlugin::class,
            [
                'context' => $this->context,
                'negotiableQuoteAddress' => $this->negotiableQuoteAddress,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'negotiableQuoteItemManagement' => $this->negotiableQuoteItemManagement,
                'recalculationStatus' => $this->recalculationStatus,
                'logger' => $this->logger,
            ]
        );
    }

    /**
     * Test aroundSave method.
     *
     * @return void
     */
    public function testAroundSave()
    {
        $quoteId = 1;
        $customerId = 3;
        $subject = $this->getMockBuilder(\Magento\Customer\Api\AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStatus'])
            ->getMockForAbstractClass();
        $proceed = function ($address) {
            return $address;
        };
        $this->context->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getParam')->with('quoteId')->willReturn($quoteId);
        $this->recalculationStatus->expects($this->once())->method('isNeedRecalculate')->willReturn(true);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willReturn(true);
        $address->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getListByCustomerId')
            ->with($customerId)
            ->willReturn([$quote]);
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::STATUS_CREATED);
        $negotiableQuote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteItemManagement->expects($this->once())
            ->method('recalculateOriginalPriceTax')
            ->with($quoteId, false, false, false)
            ->willReturn(true);

        $this->assertEquals($address, $this->plugin->aroundSave($subject, $proceed, $address));
    }

    /**
     * Test aroundSave method without quote id.
     *
     * @return void
     */
    public function testAroundSaveWithoutQuoteId()
    {
        $subject = $this->getMockBuilder(\Magento\Customer\Api\AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $proceed = function ($address) {
            return $address;
        };
        $this->context->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getParam')->with('quoteId')->willReturn(null);

        $this->assertEquals($address, $this->plugin->aroundSave($subject, $proceed, $address));
    }

    /**
     * Test aroundSave method with NoSuchEntityException.
     *
     * @return void
     */
    public function testAroundSaveWithNoSuchEntityException()
    {
        $quoteId = 1;
        $customerId = 3;
        $subject = $this->getMockBuilder(\Magento\Customer\Api\AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $proceed = function ($address) {
            return $address;
        };
        $exception = new \Magento\Framework\Exception\NoSuchEntityException(__('No such entity.'));
        $this->context->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getParam')->with('quoteId')->willReturn($quoteId);
        $this->recalculationStatus->expects($this->once())->method('isNeedRecalculate')->willReturn(true);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willThrowException($exception);
        $this->context->expects($this->once())->method('getMessageManager')->willReturn($messageManager);
        $messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Requested quote was not found'))
            ->willReturnSelf();
        $address->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getListByCustomerId')
            ->with($customerId)
            ->willReturn([]);

        $this->assertEquals($address, $this->plugin->aroundSave($subject, $proceed, $address));
    }

    /**
     * Test aroundSave method with Exception.
     *
     * @return void
     */
    public function testAroundSaveWithException()
    {
        $quoteId = 1;
        $customerId = 3;
        $subject = $this->getMockBuilder(\Magento\Customer\Api\AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $proceed = function ($address) {
            return $address;
        };
        $exception = new \Exception();
        $this->context->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getParam')->with('quoteId')->willReturn($quoteId);
        $this->recalculationStatus->expects($this->once())->method('isNeedRecalculate')->willReturn(true);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $this->context->expects($this->once())->method('getMessageManager')->willReturn($messageManager);
        $messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Unable to update shipping address'))
            ->willReturnSelf();
        $address->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getListByCustomerId')
            ->with($customerId)
            ->willReturn([]);

        $this->assertEquals($address, $this->plugin->aroundSave($subject, $proceed, $address));
    }
}
