<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

class SaveNewTest extends \Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest
{
    protected $name = 'SaveNew';

    public function testSaveNewAction()
    {
        $commentText = 'some comment';
        $visibleOnFront = true;

        $expectedPost = $this->initRequestData($commentText, $visibleOnFront);

        $this->rmaDataMapperMock->expects($this->once())->method('filterRmaSaveRequest')
            ->with($expectedPost)
            ->will($this->returnValue($expectedPost));

        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_order')
            ->will($this->returnValue($this->orderMock));
        $this->rmaModelMock->expects($this->once())
            ->method('saveRma')
            ->will($this->returnSelf());
        $this->statusHistoryMock->expects($this->once())->method('sendNewRmaEmail');
        $this->statusHistoryMock->expects($this->once())
            ->method('saveComment')
            ->with($commentText, $visibleOnFront, true);
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('You submitted the RMA request.'));

        $this->assertNull($this->action->execute());
    }

    public function testSaveNewWithWarning()
    {
        $commentText = 'some comment';
        $visibleOnFront = true;
        $exception = new \Magento\Framework\Exception\MailException(__('Message'));

        $expectedPost = $this->initRequestData($commentText, $visibleOnFront);

        $this->rmaDataMapperMock->expects($this->once())->method('filterRmaSaveRequest')
            ->with($expectedPost)
            ->will($this->returnValue($expectedPost));

        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_order')
            ->will($this->returnValue($this->orderMock));
        $this->rmaModelMock->expects($this->once())
            ->method('saveRma')
            ->will($this->returnSelf());

        $this->statusHistoryMock->expects($this->once())
            ->method('sendNewRmaEmail')
            ->will($this->throwException($exception));

        $loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Psr\Log\LoggerInterface::class)
            ->willReturn($loggerMock);
        $loggerMock->expects($this->once())->method('critical')->with($exception);
        $this->messageManagerMock->expects($this->once())
            ->method('addWarning')
            ->with(__('You did not email your customer. Please check your email settings.'));

        $this->statusHistoryMock->expects($this->once())
            ->method('saveComment')
            ->with($commentText, $visibleOnFront, true);
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('You submitted the RMA request.'));

        $this->assertNull($this->action->execute());
    }
}
