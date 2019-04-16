<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Unit test for Magento\SharedCatalog\Model\CategoryPermissions class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryPermissionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\SharedCatalog\Model\CategoryPermissions
     */
    private $model;

    /**
     * @var \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invalidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configResource = $this->getMockBuilder(
            \Magento\Framework\App\Config\ConfigResource\ConfigInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invalidatorMock = $this->createMock(
            \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator::class
        );

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\CategoryPermissions::class,
            [
                'configResource' => $this->configResource,
                'storeManager' => $this->storeManager,
                'invalidator' => $this->invalidatorMock
            ]
        );
    }

    /**
     * Test enable method.
     *
     * @return void
     */
    public function testEnable()
    {
        $this->configResource->expects($this->exactly(4))
            ->method('saveConfig')
            ->withConsecutive(
                [
                    ConfigInterface::XML_PATH_ENABLED,
                    1,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                ],
                [
                    ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW,
                    ConfigInterface::GRANT_ALL,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                ],
                [
                    ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE,
                    ConfigInterface::GRANT_ALL,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                ],
                [
                    ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS,
                    ConfigInterface::GRANT_ALL,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                ]
            )
            ->willReturnSelf();

        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->storeManager->expects($this->atLeastOnce())
            ->method('getWebsites')
            ->willReturn([$website]);
        $this->invalidatorMock->expects($this->once())->method('invalidate');

        $this->model->enable();
    }
}
