<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

/**
 * Class QuoteAdminhtmlPluginTest.
 */
class QuoteAdminhtmlPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Plugin\Quote\Model\QuoteAdminhtmlPlugin
     */
    private $quotePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->quotePlugin = $objectManager->getObject(
            \Magento\NegotiableQuote\Plugin\Quote\Model\QuoteAdminhtmlPlugin::class
        );
    }

    /**
     * Test afterGetStore() method.
     *
     * @return void
     */
    public function testAfterGetStoreWithCurrencyAvailable()
    {
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getQuoteCurrencyCode']);
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('USD');
        $store->expects($this->once())->method('getAvailableCurrencyCodes')->willReturn(['USD']);
        $currency = $this->createMock(\Magento\Directory\Model\Currency::class);
        $currency->expects($this->once())->method('getRate')->willReturn(1);
        $store->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $store->expects($this->once())->method('setCurrentCurrencyCode');
        $this->quotePlugin->afterGetStore($quote, $store);
    }

    /**
     * Test afterGetStore() method.
     *
     * @return void
     */
    public function testAfterGetStoreWithoutCurrencyAvailable()
    {
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getQuoteCurrencyCode']);
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('USD');
        $store->expects($this->once())->method('getAvailableCurrencyCodes')->willReturn(['EUR']);
        $store->expects($this->never())->method('getBaseCurrency');
        $store->expects($this->never())->method('setCurrentCurrencyCode');
        $this->quotePlugin->afterGetStore($quote, $store);
    }

    /**
     * Test aroundBeforeSave() method.
     *
     * @return void
     */
    public function testAroundBeforeSaveWithSameCurrencies()
    {
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getQuoteCurrencyCode',
                'getBaseToQuoteRate',
                'getBaseCurrencyCode',
                'getExtensionAttributes',
                'setQuoteCurrencyCode',
                'setBaseToQuoteRate',
                'setBaseCurrencyCode',
                'getShippingAssignments',
                'setShippingAssignments'
            ]
        );
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('EUR');
        $quote->expects($this->atLeastOnce())->method('getBaseToQuoteRate')->willReturn(1.5);

        $quote->expects($this->never())->method('setQuoteCurrencyCode');
        $quote->expects($this->never())->method('setBaseToQuoteRate');
        $quote->expects($this->never())->method('setBaseCurrencyCode');

        $extension = $this->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $negotiable = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $negotiable->expects($this->once())->method('getIsRegularQuote')->willReturn(1);
        $negotiable->expects($this->once())->method('getStatus')
            ->willReturn(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::STATUS_ORDERED);
        $extension->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiable);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extension);

        $proceed = function () {
        };
        $this->quotePlugin->aroundBeforeSave($quote, $proceed);
    }

    /**
     * Test aroundBeforeSave() method.
     */
    public function testAroundBeforeSaveWithDifferentCurrencies()
    {
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getQuoteCurrencyCode',
                'getBaseToQuoteRate',
                'getBaseCurrencyCode',
                'getExtensionAttributes',
                'setQuoteCurrencyCode',
                'setBaseToQuoteRate',
                'setBaseCurrencyCode'
            ]
        );
        $quote->expects($this->at(0))->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->expects($this->at(1))->method('getBaseToQuoteRate')->willReturn(1.5);
        $quote->expects($this->at(2))->method('getBaseCurrencyCode')->willReturn('EUR');

        $quote->expects($this->once())->method('setQuoteCurrencyCode');
        $quote->expects($this->once())->method('setBaseToQuoteRate');
        $quote->expects($this->once())->method('setBaseCurrencyCode');

        $extension = $this->getMockBuilder(\Magento\Quote\Api\Data\CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $negotiable = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $negotiable->expects($this->once())->method('getIsRegularQuote')->willReturn(1);
        $negotiable->expects($this->once())->method('getStatus')
            ->willReturn(\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::STATUS_ORDERED);
        $extension->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiable);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extension);

        $proceed = function () {
        };
        $this->quotePlugin->aroundBeforeSave($quote, $proceed);
    }
}
