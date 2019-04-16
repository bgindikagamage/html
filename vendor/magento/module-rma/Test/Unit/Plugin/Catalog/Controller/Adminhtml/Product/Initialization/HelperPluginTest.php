<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\HelperPlugin;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as ProductHelper;

class HelperPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HelperPlugin
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var ProductInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var ProductHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productHelperMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['setData'])
            ->getMockForAbstractClass();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(HelperPlugin::class, [
            'request' => $this->requestMock,
        ]);
    }

    public function testAfterInitialize()
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('product')
            ->willReturn([
                'use_config_is_returnable' => true,
            ]);
        $this->productMock->expects($this->once())
            ->method('setData')
            ->with('is_returnable');

        $this->model->afterInitialize($this->productHelperMock, $this->productMock);
    }

    public function testAfterInitializeWithoutParam()
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('product')
            ->willReturn([]);
        $this->productMock->expects($this->never())
            ->method('setData');

        $this->model->afterInitialize($this->productHelperMock, $this->productMock);
    }
}
