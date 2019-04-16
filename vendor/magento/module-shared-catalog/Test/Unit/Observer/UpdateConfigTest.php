<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Observer;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * Class UpdateConfigTest
 */
class UpdateConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Company\Api\StatusServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $companyStatusService;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharedCatalogModuleConfig;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configResourceMock;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observer;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \Magento\SharedCatalog\Observer\UpdateConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateConfig;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionsManagement;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->companyStatusService =
            $this->createMock(\Magento\Company\Api\StatusServiceInterface::class);
        $this->sharedCatalogModuleConfig =
            $this->createMock(\Magento\SharedCatalog\Model\Config::class);
        $this->configResourceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()->getMock();
        $this->event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getWebsite'])
            ->disableOriginalConstructor()->getMock();
        $this->observer->expects($this->any())->method('getEvent')
            ->willReturn($this->event);
        $this->catalogPermissionsManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\CatalogPermissionManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->updateConfig = $objectManager->getObject(
            \Magento\SharedCatalog\Observer\UpdateConfig::class,
            [
                'storeManager' => $this->storeManager,
                'companyStatusService' => $this->companyStatusService,
                'sharedCatalogModuleConfig' => $this->sharedCatalogModuleConfig,
                'configResource' => $this->configResourceMock,
                'catalogPermissionsManagement' => $this->catalogPermissionsManagement,
            ]
        );
    }

    /**
     * @param int $eventWebsiteId
     * @param bool $isCompanyActive
     * @param bool $isQuoteActive
     * @param int|null $methodScopeId
     * @param int $setCount
     * @return void
     * @dataProvider dataProviderExecute
     */
    public function testExecute($eventWebsiteId, $isCompanyActive, $isQuoteActive, $methodScopeId, $setCount)
    {
        $this->event->expects($this->any())->method('getWebsite')->willReturn($eventWebsiteId);

        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager->expects($this->any())->method('getWebsite')
            ->willReturn($website);

        $this->companyStatusService->expects($this->any())->method('isActive')
            ->willReturn($isCompanyActive);

        $this->sharedCatalogModuleConfig->expects($this->any())->method('isActive')
            ->willReturn($isQuoteActive);

        $isRequireModuleDisable = !$isCompanyActive && $isQuoteActive;
        $this->configResourceMock->expects(
            $this->exactly($isRequireModuleDisable ? 1 : 0)
        )->method('saveConfig');
        $this->catalogPermissionsManagement->expects($this->exactly($setCount))
            ->method('setPermissionsForAllCategories')->willReturn($methodScopeId);
        $this->catalogPermissionsManagement->expects($this->exactly($setCount))
            ->method('processAllSharedCatalogPermissions')->willReturn($methodScopeId);

        $this->updateConfig->execute($this->observer);
    }

    /**
     * @return array
     */
    public function dataProviderExecute()
    {
        return [
            [1, true, true, 1, 1],
            [0, false, true, null, 1],
            [1, false, false, 1, 0],
            [0, true, false, null, 0],
        ];
    }
}
