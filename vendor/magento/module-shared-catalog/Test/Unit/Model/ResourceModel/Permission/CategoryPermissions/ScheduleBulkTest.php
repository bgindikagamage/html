<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\Permission\CategoryPermissions;

/**
 * Test for category permissions scheduler.
 */
class ScheduleBulkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bulkManagement;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $operationFactory;

    /**
     * @var \Magento\Framework\DataObject\IdentityGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identityService;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp()
    {
        $this->bulkManagement = $this->getMockBuilder(\Magento\Framework\Bulk\BulkManagementInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->operationFactory = $this
            ->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->identityService = $this->getMockBuilder(\Magento\Framework\DataObject\IdentityGeneratorInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->groupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->scheduleBulk = $objectManager->getObject(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk::class,
            [
                'bulkManagement' => $this->bulkManagement,
                'operationFactory' => $this->operationFactory,
                'identityService' => $this->identityService,
                'serializer' => $this->serializer,
                'groupRepository' => $this->groupRepository,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $bulkId = 'bulk-001';
        $categoryIds = [1];
        $groupIds = [2];
        $userId = 3;
        $serializedData = 'operation serialized data';
        $this->identityService->expects($this->once())->method('generateId')->willReturn($bulkId);
        $operation = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializer->expects($this->once())->method('serialize')
            ->with(['category_id' => $categoryIds[0], 'group_ids' => $groupIds[0]])->willReturn($serializedData);
        $this->operationFactory->expects($this->once())->method('create')->with(
            [
                'data' => [
                    'bulk_uuid' => 'bulk-001',
                    'topic_name' => 'shared.catalog.category.permissions.updated',
                    'serialized_data' => $serializedData,
                    'status' => \Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_OPEN,
                ],
            ]
        )->willReturn($operation);
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')
            ->with($bulkId, [$operation], __('Assign Categories to Shared Catalog'), $userId)
            ->willReturn(true);
        $this->scheduleBulk->execute($categoryIds, $groupIds, $userId);
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Something went wrong while scheduling operations.
     */
    public function testExecuteWithException()
    {
        $bulkId = 'bulk-001';
        $categoryIds = [1];
        $groupIds = [2];
        $userId = 3;
        $serializedData = 'operation serialized data';
        $this->identityService->expects($this->once())->method('generateId')->willReturn($bulkId);
        $operation = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializer->expects($this->once())->method('serialize')
            ->with(['category_id' => $categoryIds[0], 'group_ids' => $groupIds[0]])->willReturn($serializedData);
        $this->operationFactory->expects($this->once())->method('create')->with(
            [
                'data' => [
                    'bulk_uuid' => 'bulk-001',
                    'topic_name' => 'shared.catalog.category.permissions.updated',
                    'serialized_data' => $serializedData,
                    'status' => \Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_OPEN,
                ],
            ]
        )->willReturn($operation);
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')
            ->with($bulkId, [$operation], __('Assign Categories to Shared Catalog'), $userId)
            ->willReturn(false);
        $this->scheduleBulk->execute($categoryIds, $groupIds, $userId);
    }
}
