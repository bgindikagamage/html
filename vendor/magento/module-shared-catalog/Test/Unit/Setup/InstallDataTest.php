<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Setup;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

/**
 * Unit test for InstallData.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InstallDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogFactory;

    /**
     * @var \Magento\SharedCatalog\Model\Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\Customer\Api\GroupManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupManagement;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taxClassRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userCollectionFactory;

    /**
     * @var \Magento\SharedCatalog\Setup\InstallData
     */
    private $installData;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->catalogFactory = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sharedCatalogRepository = $this->getMockBuilder(\Magento\SharedCatalog\Model\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupManagement = $this->getMockBuilder(\Magento\Customer\Api\GroupManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->groupRepository = $this->getMockBuilder(\Magento\Customer\Api\GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->taxClassRepository = $this->getMockBuilder(\Magento\Tax\Api\TaxClassRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->installData = $objectManagerHelper->getObject(
            \Magento\SharedCatalog\Setup\InstallData::class,
            [
                'catalogFactory' => $this->catalogFactory,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'groupManagement' => $this->groupManagement,
                'groupRepository' => $this->groupRepository,
                'taxClassRepository' => $this->taxClassRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'userCollectionFactory' => $this->userCollectionFactory,
            ]
        );
    }

    /**
     * Test for install().
     *
     * @return void.
     */
    public function testInstall()
    {
        $customerGroupId = 1;
        $sharedCatalogName = 'Default (General)';
        $sharedCatalogDescription = 'Default shared catalog';
        $taxClassId = 3;
        $customerGroupCode = 'General';

        $user = $this->getMockBuilder(\Magento\User\Api\Data\UserInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $userCollection = $this->getMockBuilder(\Magento\User\Model\ResourceModel\User\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userCollection->expects($this->once())
            ->method('setPageSize')
            ->willReturnSelf();
        $userCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($user);
        $this->userCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($userCollection);

        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('setName')->with($sharedCatalogName)->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('setDescription')->with($sharedCatalogDescription)
            ->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('setCreatedBy')->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('setType')
            ->with(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::TYPE_PUBLIC)->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('setCustomerGroupId')->with($customerGroupId)
            ->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('setTaxClassId')->with($taxClassId)->willReturnSelf();
        $this->catalogFactory->expects($this->atLeastOnce())->method('create')->willReturn($sharedCatalog);
        $defaultCustomerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $defaultCustomerGroup->expects($this->atLeastOnce())->method('getId')->willReturn($customerGroupId);
        $this->groupManagement->expects($this->atLeastOnce())->method('getDefaultGroup')
            ->willReturn($defaultCustomerGroup);
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $customerTaxClass = $this->getMockBuilder(\Magento\Tax\Api\Data\TaxClassInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerTaxClass->expects($this->atLeastOnce())->method('getClassId')->willReturn($taxClassId);
        $searchResults = $this->getMockBuilder(\Magento\Tax\Api\Data\TaxClassSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->atLeastOnce())->method('getItems')->willReturn([$customerTaxClass]);
        $this->taxClassRepository->expects($this->atLeastOnce())->method('getList')->willReturn($searchResults);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())->method('save')->with($sharedCatalog)
            ->willReturn($sharedCatalog);
        $customerGroup = $this->getMockBuilder(\Magento\Customer\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->atLeastOnce())->method('setCode')->with($customerGroupCode)->willReturnSelf();
        $this->groupRepository->expects($this->atLeastOnce())->method('getById')->with($customerGroupId)
            ->willReturn($customerGroup);
        $this->groupRepository->expects($this->atLeastOnce())->method('save')->with($customerGroup)
            ->willReturn($customerGroup);
        $moduleDataSetup = $this->getMockBuilder(\Magento\Framework\Setup\ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $moduleContext = $this->getMockBuilder(\Magento\Framework\Setup\ModuleContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->installData->install($moduleDataSetup, $moduleContext);
    }
}
