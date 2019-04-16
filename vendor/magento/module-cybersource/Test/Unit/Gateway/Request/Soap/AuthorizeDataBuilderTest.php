<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\AuthorizeDataBuilder;

/**
 * Class AuthorizeDataBuilderTest
 */
class AuthorizeDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT = 2.1111;

    const CURRENCY_CODE = 'USD';

    const SUBSCRIPTION_ID = '11111';

    /**
     * @var AuthorizeDataBuilder
     */
    protected $authorizeDataBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->authorizeDataBuilder = new AuthorizeDataBuilder();
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'ccAuthService' => [
                'run' => 'true',
            ],
            'purchaseTotals' => [
                'currency' => self::CURRENCY_CODE,
                'grandTotalAmount' => self::AMOUNT
            ],
            'recurringSubscriptionInfo' => [
                'subscriptionID' => self::SUBSCRIPTION_ID
            ]
        ];
        $result = $this->authorizeDataBuilder->build(
            [
                'payment' => $this->getPaymentMock(),
                'amount' => self::AMOUNT
            ]
        );
        static::assertEquals($expected, $result);
    }

    /**
     * Run test for build method (throw Exception)
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider buildExceptionDataProvider
     */
    public function testBuildException(array $buildSubject)
    {
        $this->authorizeDataBuilder->build($buildSubject);
    }

    public function buildExceptionDataProvider()
    {
        return [
            [['amount' => self::AMOUNT]],
            [['payment' => $this->getPaymentMock()]],
            [[]],
        ];
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $orderInstanceMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentInstanceMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(AuthorizeDataBuilder::SUBSCRIPTION_ID)
            ->willReturn(self::SUBSCRIPTION_ID);

        $paymentMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderInstanceMock);

        $orderInstanceMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE);

        return $paymentMock;
    }
}
