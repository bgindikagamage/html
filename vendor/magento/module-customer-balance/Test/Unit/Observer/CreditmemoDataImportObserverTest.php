<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CustomerBalance\Observer\CreditmemoDataImportObserver;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class CreditmemoDataImportObserverTest
 */
class CreditmemoDataImportObserverTest extends \PHPUnit\Framework\TestCase
{
    private static $refundAmount = 10;

    /** @var CreditmemoDataImportObserver|MockObject */
    private $model;

    /**
     * @var PriceCurrency|MockObject
     */
    private $priceCurrencyMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    /**
     * @var DataObject|MockObject
     */
    private $eventMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemoMock;

    protected function setUp()
    {
        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->observerMock = $objectManagerHelper->getObject(
            Observer::class,
            ['event' => $this->eventMock]
        );

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCustomerBalanceReturnMax', 'getOrder'])
            ->getMock();

        $this->model = $objectManagerHelper->getObject(
            CreditmemoDataImportObserver::class,
            [
                'priceCurrency' => $this->priceCurrencyMock,
                'scopeConfig' => $this->scopeConfigMock,
            ]
        );
    }

    public function testCreditmemoDataImport()
    {
        $rate = 2;
        $dataInput = [
            'refund_customerbalance_return' => self::$refundAmount,
            'refund_customerbalance_return_enable' => true,
            'refund_customerbalance' => true,
            'refund_real_customerbalance' => true,
        ];

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoMock->expects($this->once())
            ->method('getBaseCustomerBalanceReturnMax')
            ->willReturn(self::$refundAmount);

        $this->priceCurrencyMock->expects($this->at(0))
            ->method('round')
            ->with(self::$refundAmount)
            ->willReturnArgument(0);
        $this->priceCurrencyMock->expects($this->at(1))
            ->method('round')
            ->with(self::$refundAmount * $rate)
            ->willReturnArgument(0);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseToOrderRate'])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn($rate);

        $this->creditmemoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreditmemo', 'getInput'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemoMock);
        $eventMock->expects($this->once())
            ->method('getInput')
            ->willReturn($dataInput);
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->model->execute($observerMock);
        $this->assertEquals($this->creditmemoMock->getCustomerBalanceRefundFlag(), true);
        $this->assertEquals($this->creditmemoMock->getPaymentRefundDisallowed(), true);
        $this->assertEquals($this->creditmemoMock->getCustomerBalTotalRefunded(), self::$refundAmount * $rate);
    }

    /**
     * The store credit enabled and gift card amount refund to store credit.
     */
    public function testRefundGiftCardAmmountToStoreCredit()
    {
        $observerMock = $this->getObserverMock();

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('customer/magento_customerbalance/is_enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->model->execute($observerMock);
        $this->assertEquals($this->creditmemoMock->getCustomerBalanceRefundFlag(), true);
        $this->assertEquals($this->creditmemoMock->getPaymentRefundDisallowed(), false);
        $this->assertEquals($this->creditmemoMock->getCustomerBalTotalRefunded(), self::$refundAmount);
    }

    /**
     * The store credit disabled and gift card amount refund back.
     */
    public function testRefundGiftCardAmountWithDisabledStoreCredit()
    {
        $observerMock = $this->getObserverMock();

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('customer/magento_customerbalance/is_enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getOrder',
                    'getGiftCardsAmount',
                    'setCustomerBalanceRefundFlag',
                    'setPaymentRefundDisallowed',
                    'setCustomerBalTotalRefunded'
                ]
            )
            ->getMock();
        $this->creditmemoMock->expects($this->never())
            ->method('setCustomerBalanceRefundFlag')
            ->with(true);
        $this->creditmemoMock->expects($this->never())
            ->method('setPaymentRefundDisallowed')
            ->with(false);
        $this->creditmemoMock->expects($this->never())
            ->method('setCustomerBalTotalRefunded')
            ->with(self::$refundAmount);

        $this->model->execute($observerMock);
    }

    /**
     * Inialize the observer mock.
     *
     * @return Observer|MockObject
     */
    public function getObserverMock()
    {
        $dataInput = [
            'refund_customerbalance_return' => self::$refundAmount,
            'refund_customerbalance_return_enable' => false,
            'refund_customerbalance' => false,
            'refund_real_customerbalance' => false,
        ];

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getOrder',
                    'getGiftCardsAmount'
                ]
            )
            ->getMock();

        $this->creditmemoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->creditmemoMock->expects($this->once())
            ->method('getGiftCardsAmount')
            ->willReturn(self::$refundAmount);

        $this->priceCurrencyMock->expects($this->any())
            ->method('round')
            ->withAnyParameters()
            ->willReturn(self::$refundAmount);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreditmemo', 'getInput'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemoMock);
        $eventMock->expects($this->once())
            ->method('getInput')
            ->willReturn($dataInput);

        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);

        return $observerMock;
    }
}
