<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

class UploadSkuCsvTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UploadSkuCsv
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    protected function setUp()
    {
        $this->checkoutDataMock = $this->createMock(\Magento\AdvancedCheckout\Helper\Data::class);
        $this->cartProviderMock =
            $this->createMock(\Magento\AdvancedCheckout\Model\Observer\CartProvider::class);
        $this->cartMock = $this->createPartialMock(\Magento\AdvancedCheckout\Model\Cart::class, [
                'prepareAddProductsBySku',
                'saveAffectedProducts',
                '__wakeup'
            ]);
        $this->observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, [
                'getRequestModel',
                'getOrderCreateModel',
                '__wakeup'
            ]);

        $this->model = new \Magento\AdvancedCheckout\Model\Observer\UploadSkuCsv($this->checkoutDataMock, $this->cartProviderMock);
    }

    public function testExecuteWhenSkuFileIsNotUploaded()
    {
        $requestInterfaceMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->will($this->returnValue($requestInterfaceMock));
        $this->checkoutDataMock->expects($this->once())
            ->method('isSkuFileUploaded')->with($requestInterfaceMock)->will($this->returnValue(false));
        $this->checkoutDataMock->expects($this->never())->method('processSkuFileUploading');

        $this->model->execute($this->observerMock);
    }

    public function testExecute()
    {
        $requestInterfaceMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->will($this->returnValue($requestInterfaceMock));
        $this->checkoutDataMock->expects($this->once())
            ->method('isSkuFileUploaded')->with($requestInterfaceMock)->will($this->returnValue(true));
        $this->checkoutDataMock->expects($this->once())
            ->method('processSkuFileUploading')->will($this->returnValue(['one']));
        $orderCreateModelMock = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $this->observerMock->expects($this->once())
            ->method('getOrderCreateModel')->will($this->returnValue($orderCreateModelMock));
        $this->cartProviderMock->expects($this->once())
            ->method('get')->with($this->observerMock)->will($this->returnValue($this->cartMock));
        $this->cartMock->expects($this->once())->method('prepareAddProductsBySku')->with(['one']);
        $this->cartMock->expects($this->once())->method('saveAffectedProducts')->with($orderCreateModelMock, false);

        $this->model->execute($this->observerMock);
    }
}
