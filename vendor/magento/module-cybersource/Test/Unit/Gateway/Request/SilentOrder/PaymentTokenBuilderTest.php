<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;

/**
 * Class PaymentTokenBuilderTest
 *
 * Test for class \Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder
 */
class PaymentTokenBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TOKEN = 'test_payment_token';

    /**
     * @var PaymentTokenBuilder
     */
    protected $paymentTokenBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->paymentTokenBuilder = new PaymentTokenBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $result = $this->paymentTokenBuilder->build(['payment' => $this->getPaymentMock()]);

        $this->assertArrayHasKey(PaymentTokenBuilder::PAYMENT_TOKEN, $result);
        $this->assertEquals(self::TOKEN, $result[PaymentTokenBuilder::PAYMENT_TOKEN]);
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentInstanceMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(PaymentTokenBuilder::PAYMENT_TOKEN)
            ->willReturn(self::TOKEN);

        return $paymentMock;
    }

    /**
     * Run test build method (Exception)
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $this->paymentTokenBuilder->build(['payment' => null]);
    }
}
