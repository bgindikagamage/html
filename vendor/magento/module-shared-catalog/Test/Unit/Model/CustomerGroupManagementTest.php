<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Customer\Api\CustomerGroupConfigInterface;

/**
 * Unit tests for CustomerGroupManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerGroupManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupCollectionFactory;

    /**
     * @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalog;

    /**
     * @var \Magento\Config\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupConfigMock;

    /**
     * @var \Magento\Customer\Api\Data\GroupInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupFactory;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * @var \Magento\Customer\Api\Data\GroupInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroup;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->groupCollectionFactory = $this
            ->getMockBuilder(\Magento\Customer\Model\ResourceModel\Group\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerGroupConfigMock = $this->getMockBuilder(CustomerGroupConfigInterface::class)
            ->setMethods(['setDefaultCustomerGroup'])
            ->disableOriginalConstructor()->getMock();

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->setMethods(['getCustomerGroupId', 'getName'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->groupFactory = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();

        $this->groupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->setMethods(['save', 'deleteById', 'getById'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->setMethods(['setCode', 'setTaxClassId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->customerGroupManagement = $objectManager->getObject(
            \Magento\SharedCatalog\Model\CustomerGroupManagement::class,
            [
                'groupCollectionFactory' => $this->groupCollectionFactory,
                'customerGroupConfig' => $this->customerGroupConfigMock,
                'groupFactory' => $this->groupFactory,
                'groupRepository' => $this->groupRepository
            ]
        );
    }

    /**
     * Test for method isMasterCatalogAvailable.
     *
     * @return void
     */
    public function testIsMasterCatalogAvailable()
    {
        $customerGroupId = 164;
        $this->prepareGetGroupIdsMethod($customerGroupId);

        $this->assertTrue($this->customerGroupManagement->isMasterCatalogAvailable($customerGroupId));
    }

    /**
     * Prepare getGroupIds method.
     *
     * @param int $customerGroupId
     * @return void
     */
    private function prepareGetGroupIdsMethod($customerGroupId)
    {
        $sharedTable = 'shared_catalog';

        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->setMethods(['joinLeft', 'where'])
            ->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('joinLeft')->with(
            ['shared_catalog' => $sharedTable],
            'main_table.customer_group_id = shared_catalog.customer_group_id',
            ['shared_catalog_id' => 'shared_catalog.entity_id']
        )->willReturnSelf();
        $select->expects($this->once())->method('where')->with(
            '(shared_catalog.entity_id IS NULL AND main_table.customer_group_id != ?)',
            \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID
        )->willReturnSelf();

        $collection = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Group\Collection::class)
            ->setMethods(['getSelect', 'getTable', 'getColumnValues'])
            ->disableOriginalConstructor()->getMock();
        $collection->expects($this->exactly(2))->method('getSelect')->willReturn($select);
        $collection->expects($this->exactly(1))->method('getTable')->with($sharedTable)->willReturn($sharedTable);
        $customerGroupIds = [$customerGroupId];
        $collection->expects($this->exactly(1))->method('getColumnValues')->with('customer_group_id')
            ->willReturn($customerGroupIds);

        $this->groupCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
    }

    /**
     * Test for method setDefaultCustomerGroup.
     *
     * @return void
     */
    public function testSetDefaultCustomerGroup()
    {
        $customerGroupId = 123;
        $this->sharedCatalog->expects($this->exactly(1))->method('getCustomerGroupId')->willReturn($customerGroupId);

        $this->customerGroupConfigMock->expects($this->exactly(1))
            ->method('setDefaultCustomerGroup')->with($customerGroupId);

        $this->customerGroupManagement->setDefaultCustomerGroup($this->sharedCatalog);
    }

    /**
     * Test for setDefaultCustomerGroup() method with LocalizedException.
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Could not set default customer group
     * @return void
     */
    public function testSetDefaultCustomerGroupWithLocalizedException()
    {
        $customerGroupId = 123;

        $this->sharedCatalog->expects($this->exactly(1))->method('getCustomerGroupId')->willReturn($customerGroupId);
        $exception = new \Exception(__('Exception message'));

        $this->customerGroupConfigMock->expects($this->exactly(1))
            ->method('setDefaultCustomerGroup')->willThrowException($exception);

        $this->customerGroupManagement->setDefaultCustomerGroup($this->sharedCatalog);
    }

    /**
     * Test for method createCustomerGroupForSharedCatalog.
     *
     * @param int $calls
     * @param int $taxClassId
     * @return void
     * @dataProvider createCustomerGroupForSharedCatalogDataProvider
     */
    public function testCreateCustomerGroupForSharedCatalog($calls, $taxClassId)
    {
        $this->customerGroup->expects($this->exactly(1))->method('setCode')->willReturnSelf();
        $this->groupFactory->expects($this->exactly(1))->method('create')->willReturn($this->customerGroup);
        $sharedCatalogName = 'test catalog';
        $this->sharedCatalog->expects($this->exactly(1))->method('getName')->willReturn($sharedCatalogName);
        $this->sharedCatalog->expects($this->exactly($calls + 1))->method('getTaxClassId')->willReturn($taxClassId);
        $this->customerGroup->expects($this->exactly($calls))
            ->method('setTaxClassId')
            ->with($taxClassId)
            ->willReturn(true);
        $this->groupRepository->expects($this->exactly(1))->method('save')->willReturn($this->customerGroup);
        $result = $this->customerGroupManagement->createCustomerGroupForSharedCatalog($this->sharedCatalog);
        $this->assertEquals($this->customerGroup, $result);
    }

    /**
     * Test for createCustomerGroupForSharedCatalog() method with InvalidTransitionException.
     *
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage A customer group with this name already exists. Enter a different
     * name to create a shared catalog.
     * @return void
     */
    public function testCreateCustomerGroupForSharedCatalogWithInvalidTransitionException()
    {
        $this->customerGroup->expects($this->atLeastOnce())->method('setCode')->willReturnSelf();
        $this->groupFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->customerGroup);
        $sharedCatalogName = 'test catalog';
        $this->sharedCatalog->expects($this->atLeastOnce())->method('getName')->willReturn($sharedCatalogName);
        $this->sharedCatalog->expects($this->atLeastOnce())->method('getTaxClassId')->willReturn(false);
        $exception = new \Magento\Framework\Exception\State\InvalidTransitionException(__('test'));
        $this->groupRepository->expects($this->atLeastOnce())->method('save')->willThrowException($exception);
        $this->customerGroupManagement->createCustomerGroupForSharedCatalog($this->sharedCatalog);
    }

    /**
     * Test for createCustomerGroupForSharedCatalog() method with InvalidTransitionException.
     *
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage Could not save customer group.
     * @return void
     */
    public function testCreateCustomerGroupForSharedCatalogWithCouldNotSaveException()
    {
        $this->customerGroup->expects($this->atLeastOnce())->method('setCode')->willReturnSelf();
        $this->groupFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->customerGroup);
        $sharedCatalogName = 'test catalog';
        $this->sharedCatalog->expects($this->atLeastOnce())->method('getName')->willReturn($sharedCatalogName);
        $this->sharedCatalog->expects($this->atLeastOnce())->method('getTaxClassId')->willReturn(false);
        $exception = new \Magento\Framework\Exception\CouldNotSaveException(__('test'));
        $this->groupRepository->expects($this->atLeastOnce())->method('save')->willThrowException($exception);
        $this->customerGroupManagement->createCustomerGroupForSharedCatalog($this->sharedCatalog);
    }

    /**
     * Data provider for createCustomerGroupForSharedCatalog test.
     *
     * @return array
     */
    public function createCustomerGroupForSharedCatalogDataProvider()
    {
        return [
            ['calls' => 0, 'taxClassId' => 0],
            ['calls' => 1, 'taxClassId' => 1],
        ];
    }

    /**
     * Test for method deleteCustomerGroupById.
     *
     * @return void
     */
    public function testDeleteCustomerGroupById()
    {
        $customerGroupId = 123;
        $this->sharedCatalog->expects($this->exactly(1))->method('getCustomerGroupId')->willReturn($customerGroupId);

        $groupDeleteSuccess = true;
        $this->groupRepository->expects($this->exactly(1))->method('deleteById')->willReturn($groupDeleteSuccess);

        $result = $this->customerGroupManagement->deleteCustomerGroupById($this->sharedCatalog);
        $this->assertEquals($groupDeleteSuccess, $result);
    }

    /**
     * Test for updateCustomerGroup().
     *
     * @param int $taxClassId
     * @param string $name
     * @param int $setTaxClassIdInvokeCount
     * @param int $setCodeInvokeCount
     * @param int $saveInvokeCount
     * @param bool $result
     * @return void
     * @dataProvider updateCustomerGroupDataProvider
     */
    public function testUpdateCustomerGroup(
        $taxClassId,
        $name,
        $setTaxClassIdInvokeCount,
        $setCodeInvokeCount,
        $saveInvokeCount,
        $result
    ) {
        $customerGroupId = 1;
        $customerGroupTaxClassId = 2;
        $customerGroupCode = 'Shared Catalog';
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->any())->method('getTaxClassId')->willReturn($taxClassId);
        $sharedCatalog->expects($this->any())->method('getName')->willReturn($name);
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->any())->method('getId')->willReturn($customerGroupId);
        $customerGroup->expects($this->once())->method('getTaxClassId')->willReturn($customerGroupTaxClassId);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($customerGroupCode);
        $customerGroup->expects($this->exactly($setTaxClassIdInvokeCount))->method('setTaxClassId')->willReturnSelf();
        $customerGroup->expects($this->exactly($setCodeInvokeCount))->method('setCode')->willReturnSelf();
        $this->groupRepository->expects($this->once())->method('getById')->with($customerGroupId)
            ->willReturn($customerGroup);
        $this->groupRepository->expects($this->exactly($saveInvokeCount))->method('save')->willReturn($customerGroup);

        $this->assertEquals($result, $this->customerGroupManagement->updateCustomerGroup($sharedCatalog));
    }

    /**
     * Test for updateCustomerGroup() method with LocalizedException.
     *
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Could not update shared catalog customer group
     */
    public function testUpdateCustomerGroupWithLocalizedException()
    {
        $customerGroupId = 1;
        $customerGroupTaxClassId = 2;
        $customerGroupCode = 'Shared Catalog';
        $taxClassId = 1;
        $name = 'Shared Catalog';

        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->atLeastOnce())->method('getTaxClassId')->willReturn($taxClassId);
        $sharedCatalog->expects($this->atLeastOnce())->method('getName')->willReturn($name);
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->atLeastOnce())->method('getId')->willReturn($customerGroupId);
        $customerGroup->expects($this->once())->method('getTaxClassId')->willReturn($customerGroupTaxClassId);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($customerGroupCode);
        $customerGroup->expects($this->atLeastOnce())->method('setTaxClassId')->willReturnSelf();
        $this->groupRepository->expects($this->atLeastOnce())->method('getById')->with($customerGroupId)
            ->willReturn($customerGroup);
        $exception = new \Magento\Framework\Exception\LocalizedException(__('exception message'));
        $this->groupRepository->expects($this->once())->method('save')->willThrowException($exception);

        $this->customerGroupManagement->updateCustomerGroup($sharedCatalog);
    }

    /**
     * DataProvider for updateCustomerGroup.
     *
     * @return array
     */
    public function updateCustomerGroupDataProvider()
    {
        return [
            [2, 'Shared Catalog', 0, 0, 0, false],
            [20, 'Shared Catalog', 1, 0, 1, true],
            [2, 'Shared Catalog 1', 0, 1, 1, true],
            [20, 'Shared Catalog 1', 1, 1, 1, true],
        ];
    }

    /**
     * Test method getSharedCatalogGroupIds.
     *
     * @return void
     */
    public function testGetSharedCatalogGroupIds()
    {
        $collection = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Group\Collection::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupCollectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->setMethods(['joinLeft', 'where'])
            ->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('joinLeft')->with(
            ['shared_catalog' => 'shared_catalog'],
            'main_table.customer_group_id = shared_catalog.customer_group_id',
            ['shared_catalog_id' => 'shared_catalog.entity_id']
        )->willReturnSelf();
        $select->expects($this->once())->method('where')->with(
            '(shared_catalog.entity_id IS NOT NULL OR main_table.customer_group_id = ?)',
            \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID
        )->willReturnSelf();

        $collection->expects($this->exactly(2))->method('getSelect')->willReturn($select);
        $collection->expects($this->atLeastOnce())->method('getTable')->with('shared_catalog')->willReturnArgument(0);
        $customerGroupIds = [1];
        $collection->expects($this->exactly(1))->method('getColumnValues')->with('customer_group_id')
            ->willReturn($customerGroupIds);
        
        $this->assertEquals($customerGroupIds, $this->customerGroupManagement->getSharedCatalogGroupIds());
    }
}
