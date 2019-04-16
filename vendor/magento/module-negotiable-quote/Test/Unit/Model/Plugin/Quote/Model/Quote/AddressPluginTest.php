<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Quote\Model\Quote;

/**
 * Unit test for \Magento\NegotiableQuote\Model\Plugin\Quote\Model\Quote\AddressPlugin.
 */
class AddressPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\State|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appState;

    /**
     * @var \Magento\NegotiableQuote\Model\Plugin\Quote\Model\Quote\AddressPlugin
     */
    private $addressPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->appState =
            $this->getMockBuilder(\Magento\Framework\App\State::class)
                ->disableOriginalConstructor()
                ->getMock();
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->addressPlugin = $objectManager->getObject(
            \Magento\NegotiableQuote\Model\Plugin\Quote\Model\Quote\AddressPlugin::class,
            [
                'appState' => $this->appState
            ]
        );
    }

    /**
     * Test for afterRequestShippingRates().
     *
     * @dataProvider afterRequestShippingRatesDataProvider
     *
     * @param string $code
     * @param float $price
     * @param bool $result
     * @param int $expectedResult
     * @param PHPUnit_Framework_MockObject_Matcher_InvokedCount $call
     * @return void
     */
    public function testAfterRequestShippingRates($code, $price, $result, $expectedResult, $call)
    {
        /**
         * @var \Magento\Quote\Model\Quote\Address|\PHPUnit_Framework_MockObject_MockObject $address
         */
        $address = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
                ->disableOriginalConstructor()
                ->getMock();
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
                ->disableOriginalConstructor()
                ->getMock();
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($call)->method('getShippingPrice')->willReturn($price);
        $quoteExtensionAttributes = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteExtensionAttributes->expects($call)->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $quote->expects($call)->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $address->expects($call)->method('getQuote')->willReturn($quote);
        $rate = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Rate::class)
            ->setMethods(['getCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $rate->expects($call)->method('getCode')->willReturn($code);
        $address->expects($call)->method('getAllShippingRates')->willReturn([$rate]);
        $address->expects($call)->method('getShippingMethod')->willReturn('default');
        $address->expects($call)->method('setShippingAmount')->willReturnSelf();
        $this->appState->expects($call)->method('getAreaCode')
            ->willReturn(\Magento\Framework\App\Area::AREA_FRONTEND);

        $this->assertEquals($expectedResult, $this->addressPlugin->afterRequestShippingRates($address, $result));
    }

    /**
     * Data provider for testAfterRequestShippingRates().
     *
     * @return array
     */
    public function afterRequestShippingRatesDataProvider()
    {
        return [
            [
                'default',
                1.5,
                true,
                true,
                $this->atLeastOnce()
            ],
            [
                'default',
                0,
                true,
                true,
                $this->atLeastOnce()
            ],
            [
                'custom',
                1.5,
                true,
                true,
                $this->atLeastOnce()
            ],
            [
                'default',
                null,
                true,
                true,
                $this->atLeastOnce()
            ],
            [
                'default',
                null,
                false,
                false,
                $this->never()
            ],
        ];
    }
}
