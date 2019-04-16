<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml;

/**
 * Class AbstractJsonActionAction
 * @package Magento\SharedCatalog\Test\Unit\Controller\Adminhtml
 */
class AbstractJsonActionAction extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultJson;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    protected function setUp()
    {
        $this->resultJsonFactory =
            $this->createPartialMock(\Magento\Framework\Controller\Result\JsonFactory::class, ['create']);
    }

    /**
     * @param $data
     * @return void
     */
    protected function createJsonResponse($data)
    {
        $this->resultJson = $this->createPartialMock(\Magento\Framework\Controller\Result\Json::class, ['setJsonData']);
        $this->resultJson->expects($this->once())
            ->method('setJsonData')
            ->with(json_encode($data, JSON_NUMERIC_CHECK));
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->resultJson));
    }
}
