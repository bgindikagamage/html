<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Command;

use Magento\Cybersource\Gateway\Command\AuthorizeStrategyCommand;
use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;

class AuthorizeStrategyCommandTest extends \PHPUnit\Framework\TestCase
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
     * @var AuthorizeStrategyCommand
     */
    private $captureCommand;

    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->captureCommand = new AuthorizeStrategyCommand($this->commandPool);
    }

    public function testExecuteSecureAcceptanceAuthorize()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00'
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorizeCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN)
            ->willReturn('1111');

        $this->commandPool->expects(static::once())
            ->method('get')
            ->with(AuthorizeStrategyCommand::SECURE_ACCEPTANCE_AUTHORIZE)
            ->willReturn($authorizeCommand);
        $authorizeCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        static::assertNull($this->captureCommand->execute($commandSubject));
    }

    public function testExecuteSoapAuthorize()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00'
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorizeCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();
        $subscriptionCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN)
            ->willReturn(false);

        $authorizeCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);
        $subscriptionCommand->expects(static::once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);
        $this->commandPool->expects(static::exactly(2))
            ->method('get')
            ->willReturnMap([
                [AuthorizeStrategyCommand::SIMPLE_ORDER_SUBSCRIPTION, $subscriptionCommand],
                [AuthorizeStrategyCommand::SIMPLE_ORDER_AUTHORIZE, $authorizeCommand]
            ]);

        static::assertNull($this->captureCommand->execute($commandSubject));
    }
}
