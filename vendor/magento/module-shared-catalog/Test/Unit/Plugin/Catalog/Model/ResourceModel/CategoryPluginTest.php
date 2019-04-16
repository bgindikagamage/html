<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Model\ResourceModel;

use Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\CategoryPlugin;
use Magento\SharedCatalog\Model\CustomerGroupManagement;

/**
 * Unit tests for CategoryPlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Company\Model\CompanyContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyContext;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resource;

    /**
     * @var CategoryPlugin|\PHPUnit_Framework_MockObject_MockObject
     */
    private $categoryPluginMock;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupManagement;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->companyContext = $this->getMockBuilder(\Magento\Company\Model\CompanyContext::class)
            ->setMethods(['getCustomerGroupId'])
            ->disableOriginalConstructor()->getMock();

        $this->resource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()->getMock();

        $this->config = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()->getMock();

        $this->subject = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerGroupManagement = $this->getMockBuilder(CustomerGroupManagement::class)
            ->setMethods(['isMasterCatalogAvailable'])
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->categoryPluginMock = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\CategoryPlugin::class,
            [
                'companyContext' => $this->companyContext,
                'resource' => $this->resource,
                'config' => $this->config,
                'storeManager' => $this->storeManager,
                'customerGroupManagement' => $this->customerGroupManagement
            ]
        );
    }

    /**
     * @return void
     */
    public function testAroundGetProductCount()
    {
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()->getMock();
        $select->expects($this->exactly(1))->method('from')->will($this->returnSelf());
        $select->expects($this->exactly(2))->method('where')->will($this->returnSelf());
        $select->expects($this->exactly(1))->method('joinLeft')->will($this->returnSelf());
        $select->expects($this->exactly(1))->method('joinInner')->will($this->returnSelf());

        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())->method('isActive')->willReturn(true);

        $connect = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $connect->expects($this->once())->method('select')->willReturn($select);
        $connect->expects($this->once())->method('fetchOne')->willReturn(5);

        $this->subject->expects($this->exactly(2))->method('getConnection')->willReturn($connect);

        $category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()->getMock();

        $method = function () {
        };
        $this->categoryPluginMock->aroundGetProductCount($this->subject, $method, $category);
    }

    /**
     * Test for aroundGetProductCount() method when module is not active.
     *
     * @return void
     */
    public function testAroundGetProductCountWhenConfigInactive()
    {
        $customerGroupId = 1;
        $this->companyContext->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())->method('isActive')->willReturn(false);
        $this->customerGroupManagement->expects($this->never())->method('isMasterCatalogAvailable');
        $category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()->getMock();
        $method = function () {
        };

        $this->categoryPluginMock->aroundGetProductCount($this->subject, $method, $category);
    }

    /**
     * Test for aroundGetProductCount() method when master catalog is available.
     *
     * @return void
     */
    public function testAroundGetProductCountWhenMasterCatalogAvailable()
    {
        $customerGroupId = 1;
        $this->companyContext->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())->method('isActive')->willReturn(true);
        $this->customerGroupManagement->expects($this->once())->method('isMasterCatalogAvailable')
            ->with($customerGroupId)
            ->willReturn(true);
        $category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()->getMock();
        $method = function () {
        };

        $this->categoryPluginMock->aroundGetProductCount($this->subject, $method, $category);
    }

    /**
     * Test for afterGetParentCategories().
     *
     * @return void
     */
    public function testAfterGetParentCategories()
    {
        $category = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getIsActive'])
            ->disableOriginalConstructor()->getMock();
        $categoryIsActive = true;
        $category->expects($this->exactly(1))->method('getIsActive')->willReturn($categoryIsActive);

        $categories = [$category];
        $result = $this->categoryPluginMock->afterGetParentCategories($this->subject, $categories);
        $this->assertEquals($categories, $result);
    }
}
