<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk;

/**
 * Unit test for Magento\SharedCatalog\Model\SharedCatalogBulkPublisher class.
 */
class SharedCatalogBulkPublisherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionsConfig;

    /**
     * @var ScheduleBulk|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scheduleBulk;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userContext;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->permissionsConfig = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scheduleBulk = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(\Magento\Authorization\Model\UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\SharedCatalogBulkPublisher::class,
            [
                'permissionsConfig' => $this->permissionsConfig,
                'scheduleBulk' => $this->scheduleBulk,
                'userContext' => $this->userContext,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test scheduleCategoryPermissionsUpdate method.
     *
     * @return void
     */
    public function testScheduleCategoryPermissionsUpdate()
    {
        $userId = 1;
        $userType = 2;
        $categoryIds = [1, 2];
        $groupIds = [1, 2];
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->permissionsConfig->expects($this->once())->method('isActive')->willReturn(true);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->once())->method('getUserType')->willReturn($userType);
        $this->scheduleBulk->expects($this->once())
            ->method('execute')
            ->with($categoryIds, $groupIds, $userId)
            ->willReturn($userId);

        $this->model->scheduleCategoryPermissionsUpdate($categoryIds, $groupIds);
    }
}
