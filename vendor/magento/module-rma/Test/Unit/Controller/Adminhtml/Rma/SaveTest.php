<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

class SaveTest extends \Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest
{
    protected $name = 'Save';

    public function testSaveAction()
    {
        $rmaId = 1;
        $commentText = 'some comment';
        $previousStatus = 'pending';
        $status = 'authorized';
        $visibleOnFront = true;

        $this->rmaModelMock->setStatus($status);
        $this->rmaModelMock->setOrigData('status', $previousStatus);
        $this->rmaModelMock->setData('is_send_auth_email', 1);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->will(
                $this->returnValueMap(
                    [
                        ['rma_id', null, $rmaId],
                    ]
                )
            );
        $expectedPost = $this->initRequestData($commentText, $visibleOnFront);

        $this->rmaDataMapperMock->expects($this->once())->method('filterRmaSaveRequest')
            ->with($expectedPost)
            ->will($this->returnValue($expectedPost));
        $this->rmaDataMapperMock->expects($this->once())->method('combineItemStatuses')
            ->with($expectedPost['items'], $rmaId)
            ->will($this->returnValue([]));

        $this->rmaModelMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($rmaId));
        $this->rmaModelMock->expects($this->any())
            ->method('setStatus')
            ->will($this->returnSelf());
        $this->rmaModelMock->expects($this->once())
            ->method('saveRma')
            ->will($this->returnSelf());
        $this->statusHistoryMock->expects($this->once())->method('sendAuthorizeEmail');
        $this->statusHistoryMock->expects($this->once())
            ->method('saveSystemComment');

        $this->assertNull($this->action->execute());
    }
}
