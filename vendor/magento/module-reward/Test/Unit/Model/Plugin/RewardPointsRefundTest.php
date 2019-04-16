<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory as HistoryCollectionFactory;
use Magento\Reward\Model\ResourceModel\Reward\History\Collection as HistoryCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditMemoResourceModel;
use Magento\Reward\Helper\Data as RewardData;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Reward\Model\Reward\Refund\SalesRuleRefund as SalesRuleRefund;
use Magento\Sales\Model\Order\Creditmemo as CreditMemoModel;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Reward\Model\Reward\History as RewardHistory;
use Magento\Store\Model\Store as StoreModel;
use Magento\Reward\Model\Reward as RewardModel;
use Magento\Reward\Model\ResourceModel\Reward\History as HistoryResourceModel;
use Magento\Reward\Model\Plugin\RewardPointsRefund;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RewardPointsRefundTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RewardData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardData;

    /**
     * @var RewardFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardFactory;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var HistoryCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $historyCollectionFactory;

    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventManager;

    /**
     * @var SalesRuleRefund|\PHPUnit_Framework_MockObject_MockObject
     */
    private $salesRuleRefund;

    /**
     * @var CreditMemoModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditMemo;

    /**
     * @var OrderModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var CreditMemoResourceModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditMemoResourceModel;

    /**
     * @var RewardHistory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardHistory;

    /**
     * @var StoreModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var HistoryCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $historyCollection;

    /**
     * @var RewardModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reward;

    /**
     * @var HistoryResourceModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $history;

    /**
     * @var RewardPointsRefund
     */
    private $plugin;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardData = $this->getMockBuilder(RewardData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactory = $this->getMockBuilder(RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->historyCollectionFactory = $this->getMockBuilder(HistoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->salesRuleRefund = $this->getMockBuilder(SalesRuleRefund::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditMemo = $this->getMockBuilder(CreditMemoModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getOrder',
                    'getAutomaticallyCreated',
                    'getBaseRewardCurrencyAmount',
                    'getRewardedAmountAfterRefund',
                    'setRewardedAmountAfterRefund'
                ]
            )
            ->getMock();
        $this->order = $this->getMockBuilder(OrderModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomerId',
                    'getStore',
                    'getIncrementId',
                    'getStoreId',
                    'getBaseGrandTotal',
                    'getBaseTaxAmount',
                    'getBaseShippingAmount',
                    'getBaseTotalRefunded',
                    'getBaseTaxRefunded',
                    'getBaseShippingRefunded'
                ]
            )
            ->getMock();
        $this->creditMemoResourceModel = $this->getMockBuilder(CreditMemoResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardHistory = $this->getMockBuilder(RewardHistory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addCustomerFilter',
                    'addWebsiteFilter',
                    'addFilter',
                    'getAdditionalData',
                    'getPointsVoided',
                    'getPointsDelta',
                    'getResource'
                ]
            )
            ->getMock();
        $this->store = $this->getMockBuilder(StoreModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $this->historyCollection = $this->getMockBuilder(HistoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reward = $this->getMockBuilder(RewardModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['setWebsiteId', 'setCustomerId', 'loadByCustomer', 'getPointsBalance'])
            ->getMock();
        $this->history = $this->getMockBuilder(HistoryResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = $objectManager->getObject(
            RewardPointsRefund::class,
            [
                'historyCollectionFactory' => $this->historyCollectionFactory,
                'storeManager' => $this->storeManager,
                'eventManager' => $this->eventManager,
                'rewardFactory' => $this->rewardFactory,
                'rewardData' => $this->rewardData,
                'salesRuleRefund' => $this->salesRuleRefund
            ]
        );
    }

    public function testBeforeSave()
    {
        $this->creditMemo->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->creditMemo->expects($this->once())
            ->method('getAutomaticallyCreated')
            ->willReturn(null);
        $this->creditMemo->expects($this->once())
            ->method('getBaseRewardCurrencyAmount')
            ->willReturn(null);

        $this->plugin->beforeSave($this->creditMemoResourceModel, $this->creditMemo);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterSave()
    {
        $grandTotal = 655;
        $taxAmount = 0;
        $shippingCost = 5;
        $pointsDelta = 120;
        $pointsBalance = 240;
        $incrementId = '000000002';
        $additionalData = [
            'increment_id' => $incrementId,
            'rate' => [
                'points' => '20',
                'currency_amount' => '100.0000',
                'direction' => '2',
                'currency_code' => 'USD'
            ]
        ];
        $this->creditMemo->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->historyCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->historyCollection);
        $this->historyCollection->expects($this->once())
            ->method('addCustomerFilter')
            ->willReturnSelf();
        $this->historyCollection->expects($this->once())
            ->method('addWebsiteFilter')
            ->willReturnSelf();
        $this->historyCollection->expects($this->any())
            ->method('addFilter')
            ->willReturnSelf();

        $this->order->expects($this->atLeastOnce())
            ->method('getCustomerId')
            ->willReturn(1);
        $this->order->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($incrementId);
        $this->store->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn(0);

        $this->historyCollection->expects($this->atLeastOnce())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->rewardHistory]));

        $this->rewardHistory->expects($this->atLeastOnce())
            ->method('getAdditionalData')
            ->willReturn($additionalData);
        $this->salesRuleRefund->expects($this->once())
            ->method('refund')
            ->willReturnSelf();

        $this->order->expects($this->once())
            ->method('getBaseGrandTotal')
            ->willReturn($grandTotal);
        $this->order->expects($this->once())
            ->method('getBaseTaxAmount')
            ->willReturn($taxAmount);
        $this->order->expects($this->once())
            ->method('getBaseShippingAmount')
            ->willReturn($shippingCost);
        $this->order->expects($this->once())
            ->method('getBaseTotalRefunded')
            ->willReturn($grandTotal);
        $this->order->expects($this->once())
            ->method('getBaseTaxRefunded')
            ->willReturn($taxAmount);
        $this->order->expects($this->once())
            ->method('getBaseShippingRefunded')
            ->willReturn($shippingCost);

        $this->creditMemo->expects($this->once())
            ->method('setRewardedAmountAfterRefund')
            ->willReturnSelf();
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->willReturnSelf();
        $this->creditMemo->expects($this->once())
            ->method('getRewardedAmountAfterRefund')
            ->willReturn(0);
        $this->rewardHistory->expects($this->once())
            ->method('getPointsVoided')
            ->willReturn('0');
        $this->rewardHistory->expects($this->once())
            ->method('getPointsDelta')
            ->willReturn($pointsDelta);

        $this->rewardFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->reward);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->order->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);
        $this->reward->expects($this->once())
            ->method('setWebsiteId')
            ->willReturnSelf();
        $this->reward->expects($this->once())
            ->method('setCustomerId')
            ->willReturnSelf();
        $this->reward->expects($this->once())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->reward->expects($this->once())
            ->method('getPointsBalance')
            ->willReturn($pointsBalance);

        $this->rewardData->expects($this->once())
            ->method('getGeneralConfig')
            ->willReturn(null);

        $this->rewardHistory->expects($this->once())
            ->method('getResource')
            ->willReturn($this->history);
        $this->history->expects($this->once())
            ->method('updateHistoryRow')
            ->willReturnSelf();

        $this->assertSame(
            $this->creditMemoResourceModel,
            $this->plugin->afterSave($this->creditMemoResourceModel, $this->creditMemoResourceModel, $this->creditMemo)
        );
    }
}
