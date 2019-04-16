<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Guest;

class CreateTest extends \Magento\Rma\Test\Unit\Controller\GuestTest
{
    /**
     * @var string
     */
    protected $name = 'Create';

    public function testCreateAction()
    {
        $orderId = 2;
        $post = ['customer_custom_email' => true, 'items' => ['1', '2'], 'rma_comment' => 'comment'];

        $preparedUrl = '/prepared/Url/';
        $successUrl = '/success/Url/';

        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'getCustomerId', 'load', 'getId']
        );
        $order->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));

        $dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $rma = $this->createMock(\Magento\Rma\Model\Rma::class);
        $rma->expects($this->once())
            ->method('setData')
            ->will($this->returnSelf());
        $rma->expects($this->once())
            ->method('saveRma')
            ->will($this->returnSelf());
        $history1 = $this->createMock(\Magento\Rma\Model\Rma\Status\History::class);
        $history2 = $this->createMock(\Magento\Rma\Model\Rma\Status\History::class);
        $this->rmaHelper->expects($this->once())
            ->method('canCreateRma')
            ->with($orderId)
            ->will($this->returnValue(true));
        $this->salesGuestHelper->expects($this->once())
            ->method('loadValidOrder')
            ->with($this->request)
            ->will($this->returnValue(true));

        $this->objectManager->expects($this->at(0))
            ->method('get')
            ->with(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->will($this->returnValue($dateTime));
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with(\Magento\Rma\Model\Rma::class)
            ->will($this->returnValue($rma));
        $this->objectManager->expects($this->at(2))
            ->method('create')
            ->with(\Magento\Rma\Model\Rma\Status\History::class)
            ->will($this->returnValue($history1));
        $this->objectManager->expects($this->at(3))
            ->method('create')
            ->with(\Magento\Rma\Model\Rma\Status\History::class)
            ->will($this->returnValue($history2));

        $this->request->expects($this->once())
            ->method('getPostValue')
            ->will($this->returnValue($post));
        $this->coreRegistry->expects($this->once())
            ->method('registry')
            ->with('current_order')
            ->will($this->returnValue($order));
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('*/*/returns')
            ->willReturn($preparedUrl);

        $this->redirect->expects($this->once())
            ->method('success')
            ->with($preparedUrl)
            ->willReturn($successUrl);

        $this->resultRedirect->expects($this->once())
            ->method('setUrl')
            ->with($successUrl)
            ->willReturnSelf();

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $this->controller->execute());
    }
}
