<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class CreditmemoRefundTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\CreditmemoRefund
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->subject = $objectManager->getObject(\Magento\Reward\Observer\CreditmemoRefund::class, []);
    }

    public function testCreditmemoRefund()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $creditmemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['getBaseRewardCurrencyAmount', '__wakeup', 'getOrder']
        );
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getBaseRwrdCrrncyAmntRefnded', 'getBaseRwrdCrrncyAmtInvoiced', '__wakeup', 'setForcedCanCreditmemo']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getCreditmemo']);
        $eventMock->expects($this->exactly(2))->method('getCreditmemo')->will($this->returnValue($creditmemoMock));

        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));
        $creditmemoMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->will($this->returnValue(10));
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->will($this->returnValue(25));
        $orderMock->expects($this->once())->method('setForcedCanCreditmemo')->with(false)->will($this->returnSelf());

        $creditmemoMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->will($this->returnValue(15));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
