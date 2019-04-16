<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Command;

use Magento\Cybersource\Gateway\Command\CaptureStrategyCommand;
use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;

class CaptureStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $commandPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var CaptureStrategyCommand
     */
    private $captureCommand;

    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->captureCommand = new CaptureStrategyCommand($this->commandPool);
    }

    public function testExecuteSecureAcceptanceOrderSale()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00'
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saleCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);

        $paymentInfo->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN)
            ->willReturn('1111');

        $this->commandPool->expects(static::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SECURE_ACCEPTANCE_SALE)
            ->willReturn($saleCommand);
        $saleCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        static::assertNull($this->captureCommand->execute($commandSubject));
    }

    public function testExecuteSoapOrderSale()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00'
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saleCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();
        $subscriptionCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);

        $paymentInfo->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN)
            ->willReturn(false);

        $subscriptionCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $saleCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $this->commandPool->expects(static::exactly(2))
            ->method('get')
            ->willReturnMap([
                [CaptureStrategyCommand::SIMPLE_ORDER_SUBSCRIPTION, $subscriptionCommand],
                [CaptureStrategyCommand::SIMPLE_ORDER_SALE, $saleCommand]
            ]);

        static::assertNull($this->captureCommand->execute($commandSubject));
    }

    /**
     * Tests Simple Order API Capture
     */
    public function testExecuteSOAPOrderCapture()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00'
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();
        $transactionMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment\Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn($transactionMock);

        $this->commandPool->expects(static::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SIMPLE_ORDER_CAPTURE)
            ->willReturn($captureCommand);
        $captureCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        static::assertNull($this->captureCommand->execute($commandSubject));
    }
}
