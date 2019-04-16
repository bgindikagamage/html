<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\ProductItem\Price;

use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test for ProductItem\Price\ScheduleBulk resource model.
 */
class ScheduleBulkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var BulkManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bulkManagementMock;

    /**
     * @var OperationInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $operationFactoryMock;

    /**
     * @var IdentityGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identityServiceMock;

    /**
     * @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogMock;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $operationMock;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->bulkManagementMock = $this->getMockBuilder(BulkManagementInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->operationFactoryMock = $this->getMockBuilder(OperationInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->identityServiceMock = $this->getMockBuilder(IdentityGeneratorInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->operationMock = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->groupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scheduleBulk = $this->objectManagerHelper->getObject(
            \Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk::class,
            [
                'bulkManagement' => $this->bulkManagementMock,
                'operationFactory' => $this->operationFactoryMock,
                'identityService' => $this->identityServiceMock,
                'groupRepository' => $this->groupRepository
            ]
        );
    }

    /**
     * Prepare for groupRepository mock.
     *
     * @return void
     */
    private function prepareGroupRepositoryMock()
    {
        $customerGroupId  = 324;
        $this->sharedCatalogMock->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->setMethods(['getCode'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $customerGroupCode = 'code23246';
        $customerGroup->expects($this->atLeastOnce())->method('getCode')->willReturn($customerGroupCode);

        $this->groupRepository->expects($this->atLeastOnce())->method('getById')->willReturn($customerGroup);
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $userId = 1664;
        $price = [['is_changed' => true]];
        $prices = ['sku_1' => $price];
        $bulkUuid = '83900a60-57c9-11e6-8b77-86f30ca893d3';
        $bulkDescription = __('Assign custom prices to selected products');
        $this->getMockForOperation($bulkUuid);
        $this->bulkManagementMock
            ->expects($this->once())
            ->method('scheduleBulk')
            ->with($bulkUuid, [$this->operationMock], $bulkDescription, $userId)
            ->willReturn(true);

        $this->prepareGroupRepositoryMock();

        $this->scheduleBulk->execute($this->sharedCatalogMock, $prices, $userId);
    }

    /**
     * Test for execute() with Exception.
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Something went wrong while processing the request.
     * @return void
     */
    public function testExecuteWithException()
    {
        $userId = 435;
        $price = [['is_changed' => true]];
        $prices = ['sku_1' => $price];
        $bulkUuid = '83900a60-57c9-11e6-8b77-86f30ca893d3';
        $bulkDescription = __('Assign custom prices to selected products');
        $this->getMockForOperation($bulkUuid);
        $this->operationFactoryMock->expects($this->once())->method('create')->willReturn($this->operationMock);
        $this->bulkManagementMock
            ->expects($this->once())
            ->method('scheduleBulk')
            ->with($bulkUuid, [$this->operationMock], $bulkDescription, $userId)
            ->willReturn(false);

        $this->prepareGroupRepositoryMock();

        $this->scheduleBulk->execute($this->sharedCatalogMock, $prices, $userId);
    }

    /**
     * Get mock for operation.
     *
     * @param string $bulkUuid
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockForOperation($bulkUuid)
    {
        $this->identityServiceMock->expects($this->once())->method('generateId')->willReturn($bulkUuid);
        $this->sharedCatalogMock->expects($this->once())->method('getId')->willReturn(5);
        $this->operationFactoryMock->expects($this->once())->method('create')->willReturn($this->operationMock);
        return $this->operationMock;
    }
}
