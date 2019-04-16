<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\Permission\CategoryPermissions;

/**
 * Test for category permissions consumer.
 */
class ConsumerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \Magento\Framework\EntityManager\EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionsManagement;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\Consumer
     */
    private $consumer;

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp()
    {
        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->entityManager = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->catalogPermissionsManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\CatalogPermissionManagement::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogInvalidation = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogInvalidation::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->consumer = $objectManager->getObject(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\Consumer::class,
            [
                'logger' => $this->logger,
                'entityManager' => $this->entityManager,
                'serializer' => $this->serializer,
                'catalogPermissionsManagement' => $this->catalogPermissionsManagement,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
            ]
        );
    }

    /**
     * Test for processOperations method.
     *
     * @return void
     */
    public function testProcessOperations()
    {
        $data = ['category_id' => 1, 'group_ids' => '2,3'];
        $operation = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterface::class)
            ->disableOriginalConstructor()->getMock();
        $operationList = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationListInterface::class)
            ->disableOriginalConstructor()->getMock();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([$operation]);
        $operation->expects($this->once())->method('getSerializedData')->willReturn(json_encode($data));
        $this->serializer->expects($this->once())->method('unserialize')->with(json_encode($data))->willReturn($data);
        $this->catalogPermissionsManagement->expects($this->once())
            ->method('updateCategoryPermissions')->with($data['category_id'], explode(',', $data['group_ids']));
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('reindexCatalogPermissions')->with([$data['category_id']]);
        $operation->expects($this->once())->method('setStatus')
            ->with(\Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_COMPLETE)
            ->willReturnSelf();
        $operation->expects($this->once())->method('setResultMessage')->with(null)->willReturnSelf();
        $this->entityManager->expects($this->once())->method('save')->with($operationList)->willReturn($operationList);
        $this->consumer->processOperations($operationList);
    }
}
