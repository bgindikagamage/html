<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\Framework\Search\Adapter\Mysql;

use Magento\SharedCatalog\Model\CustomerGroupManagement;

/**
 * Unit tests for IndexBuilderInterfacePlugin.
 */
class IndexBuilderInterfacePluginTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\SharedCatalog\Plugin\Framework\Search\Adapter\Mysql\IndexBuilderInterfacePlugin
     */
    private $indexBuilderInterfacePlugin;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

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
        $this->config = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()->getMock();
        $this->companyContext = $this->getMockBuilder(\Magento\Company\Model\CompanyContext::class)
            ->setMethods(['getCustomerGroupId'])
            ->disableOriginalConstructor()->getMock();
        $this->customerGroupManagement = $this->getMockBuilder(CustomerGroupManagement::class)
            ->setMethods(['isMasterCatalogAvailable'])
            ->disableOriginalConstructor()->getMock();
        $this->resource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->indexBuilderInterfacePlugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\Framework\Search\Adapter\Mysql\IndexBuilderInterfacePlugin::class,
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
    public function testAfterBuild()
    {
        $subject = $this->getMockBuilder(\Magento\Framework\Search\Adapter\Mysql\IndexBuilderInterface::class)
            ->disableOriginalConstructor()->getMock();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())->method('isActive')->willReturn(true);
        $select->expects($this->once())->method('where')->will($this->returnSelf());
        $select->expects($this->any())->method('joinLeft')->will($this->returnSelf());
        $select->expects($this->any())->method('joinInner')->will($this->returnSelf());

        $this->indexBuilderInterfacePlugin->afterBuild($subject, $select);
    }

    /**
     * Test for afterBuild() method when module is not active.
     *
     * @return void
     */
    public function testAfterBuildWhenConfigInactive()
    {
        $customerGroupId = 1;
        $this->companyContext->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())->method('isActive')->willReturn(false);
        $this->customerGroupManagement->expects($this->never())->method('isMasterCatalogAvailable');
        $subject = $this->getMockBuilder(\Magento\Framework\Search\Adapter\Mysql\IndexBuilderInterface::class)
            ->disableOriginalConstructor()->getMock();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();

        $this->indexBuilderInterfacePlugin->afterBuild($subject, $select);
    }

    /**
     * Test for afterBuild() method when master catalog is available.
     *
     * @return void
     */
    public function testAfterBuildWhenMasterCatalogAvailable()
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
        $subject = $this->getMockBuilder(\Magento\Framework\Search\Adapter\Mysql\IndexBuilderInterface::class)
            ->disableOriginalConstructor()->getMock();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)->disableOriginalConstructor()->getMock();

        $this->indexBuilderInterfacePlugin->afterBuild($subject, $select);
    }
}
