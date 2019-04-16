<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Plugin\SalesRule\Observer;

use \Magento\SalesRule\Observer\SalesOrderAfterPlaceObserver;

/**
 * Class SalesOrderAfterPlaceObserverPluginTest
 */
class SalesOrderAfterPlaceObserverPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Plugin\SalesRule\Observer\SalesOrderAfterPlaceObserverPlugin
     */
    private $salesOrderAfterPlaceObserverPlugin;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->salesOrderAfterPlaceObserverPlugin = $objectManager->getObject(
            \Magento\NegotiableQuote\Plugin\SalesRule\Observer\SalesOrderAfterPlaceObserverPlugin::class,
            [
                'quoteRepository' => $this->quoteRepository
            ]
        );
    }

    /**
     * Test aroundExecute
     */
    public function testAroundExecute()
    {
        /**
         * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject $observer
         */
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $quote = $this->createMock(\Magento\Quote\Api\Data\CartInterface::class);
        $this->prepareMocks($observer, $quote);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        /**
         * @var SalesOrderAfterPlaceObserver|\PHPUnit_Framework_MockObject_MockObject $subject
         */
        $subject = $this->createMock(\Magento\SalesRule\Observer\SalesOrderAfterPlaceObserver::class);
        $proceed = function ($observer) {
            return $observer;
        };

        $this->assertInstanceOf(
            \Magento\Framework\Event\Observer::class,
            $this->salesOrderAfterPlaceObserverPlugin->aroundExecute($subject, $proceed, $observer)
        );
    }

    /**
     * Test aroundExecute with NoSuchEntityException
     */
    public function testAroundExecuteWithNoSuchEntityException()
    {
        /**
         * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject $observer
         */
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $quote = $this->createMock(\Magento\Quote\Api\Data\CartInterface::class);
        $this->prepareMocks($observer, $quote);
        $phrase = new \Magento\Framework\Phrase(__('Exception'));
        $exception = new \Magento\Framework\Exception\NoSuchEntityException($phrase);
        $this->quoteRepository->expects($this->any())->method('get')->willThrowException($exception);
        /**
         * @var SalesOrderAfterPlaceObserver|\PHPUnit_Framework_MockObject_MockObject $subject
         */
        $subject = $this->createMock(\Magento\SalesRule\Observer\SalesOrderAfterPlaceObserver::class);
        $proceed = function ($observer) {
            return $observer;
        };

        $this->assertInstanceOf(
            \Magento\Framework\Event\Observer::class,
            $this->salesOrderAfterPlaceObserverPlugin->aroundExecute($subject, $proceed, $observer)
        );
    }

    /**
     * Prepare mocks
     *
     * @param \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject $observer
     * @param \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject $quote
     */
    private function prepareMocks($observer, $quote)
    {
        $order = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->setMethods(['getOrder', 'getQuoteId', 'getDiscountAmount', 'getAppliedRuleIds', 'setDiscountAmount'])
            ->getMockForAbstractClass();
        $order->expects($this->any())->method('getOrder')->willReturn($order);
        $order->expects($this->any())->method('getQuoteId')->willReturn(1);
        $order->expects($this->any())->method('getDiscountAmount')->willReturn(0);
        $order->expects($this->any())->method('getAppliedRuleIds')->willReturn([1]);
        $order->expects($this->any())->method('getDiscountAmount')->willReturn(0);
        $order->expects($this->any())->method('setDiscountAmount')->willReturnSelf();
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder'], []);
        $event->expects($this->any())->method('getOrder')->willReturn($order);
        $observer->expects($this->any())->method('getEvent')->willReturn($event);
        $negotiableQuote = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $negotiableQuote->expects($this->any())->method('getAppliedRuleIds')->willReturn([1]);
        $extensionAttributes = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);
    }
}
