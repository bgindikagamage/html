<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model\SaveHandler;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;

/**
 * Unit tests for SharedCatalog save handler.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog
     */
    private $sharedCatalogSaveHandler;

    /**
     * @var ProductItemManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogProductItemManagementMock;

    /**
     * @var CustomerGroupManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupManagementMock;

    /**
     * @var SharedCatalogManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogManagementMock;

    /**
     * @var CatalogPermissionManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionManagementMock;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validatorMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save|\PHPUnit_Framework_MockObject_MockObject
     */
    private $saveMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->sharedCatalogProductItemManagementMock = $this->getMockBuilder(ProductItemManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupManagementMock = $this->getMockBuilder(CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogManagementMock = $this->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogPermissionManagementMock = $this->getMockBuilder(CatalogPermissionManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->saveMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogSaveHandler = $this->objectManagerHelper->getObject(
            \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog::class,
            [
                'sharedCatalogProductItemManagement' => $this->sharedCatalogProductItemManagementMock,
                'customerGroupManagement' => $this->customerGroupManagementMock,
                'sharedCatalogManagement' => $this->sharedCatalogManagementMock,
                'catalogPermissionManagement' => $this->catalogPermissionManagementMock,
                'validator' => $this->validatorMock,
                'logger' => $this->loggerMock,
                'save' => $this->saveMock
            ]
        );
    }

    /**
     * Test for execute() method.
     *
     * @param string|null $sharedCatalogType
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $addPricesInvocation
     * @dataProvider executeDataProvider
     * @return void
     */
    public function testExecute($sharedCatalogType, $addPricesInvocation)
    {
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalSharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn(null);
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')->willReturn($sharedCatalogType);
        $this->customerGroupManagementMock->expects($addPricesInvocation)->method('getSharedCatalogGroupIds')
            ->willReturn([1]);
        $this->catalogPermissionManagementMock->expects($addPricesInvocation)->method('reassignForRootCategories');
        $this->customerGroupManagementMock->expects($this->once())->method('updateCustomerGroup');
        $originalSharedCatalog->expects($this->once())->method('getId')->willReturn(false);
        $this->catalogPermissionManagementMock->expects($this->once())
            ->method('setDenyPermissionsForCustomerGroup')->with(null);
        $this->sharedCatalogProductItemManagementMock->expects($addPricesInvocation)
            ->method('addPricesForPublicCatalog');
        $this->saveMock->expects($this->once())->method('prepare')->with($sharedCatalog);
        $this->saveMock->expects($this->once())->method('execute')->with($sharedCatalog);
        $this->validatorMock->expects($this->once())->method('isDirectChangeToCustom')->with($sharedCatalog);
        $this->sharedCatalogManagementMock->expects($this->once())->method('isPublicCatalogExist')
            ->willReturn(true);

        $this->assertInstanceOf(
            \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class,
            $this->sharedCatalogSaveHandler->execute($sharedCatalog, $originalSharedCatalog)
        );
    }

    /**
     * Data Provider for execute() test.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC,
                $this->atLeastOnce()
            ],
            [
                null,
                $this->never()
            ]
        ];
    }

    /**
     * Test for execute() method with LocalizedException.
     *
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage Could not save shared catalog.
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $exception = new \Magento\Framework\Exception\LocalizedException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with('exception message');

        $this->sharedCatalogSaveHandler->execute($sharedCatalog, $sharedCatalog);
    }

    /**
     * Test for execute() method with CouldNotSaveException.
     *
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage exception message
     * @return void
     */
    public function testExecuteWithCouldNotSaveException()
    {
        $exception = new \Magento\Framework\Exception\CouldNotSaveException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);

        $this->sharedCatalogSaveHandler->execute($sharedCatalog, $sharedCatalog);
    }

    /**
     * Prepare mocks for execute() test with Exceptions.
     *
     * @param \Exception $exception
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareExecuteWithExceptions(\Exception $exception)
    {
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')
            ->willReturn(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC);
        $this->customerGroupManagementMock->expects($this->never())->method('createCustomerGroupForSharedCatalog');
        $this->saveMock->expects($this->once())->method('prepare')->with($sharedCatalog)
            ->willThrowException($exception);
        $this->validatorMock->expects($this->once())->method('isDirectChangeToCustom')->with($sharedCatalog);
        $this->sharedCatalogManagementMock->expects($this->once())->method('isPublicCatalogExist')
            ->willReturn(true);

        return $sharedCatalog;
    }
}
