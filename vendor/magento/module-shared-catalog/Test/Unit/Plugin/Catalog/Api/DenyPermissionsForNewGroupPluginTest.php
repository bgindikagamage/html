<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Api;

/**
 * Unit test for Magento\SharedCatalog\Plugin\Catalog\Api\DenyPermissionsForNewCategoryPlugin class.
 */
class DenyPermissionsForNewCategoryPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\Config\CategoryPermission|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configCategoryPermission;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\SharedCatalog\Plugin\Catalog\Api\DenyPermissionsForNewCategoryPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configCategoryPermission = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\Config\CategoryPermission::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogPermissionManagement = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\CatalogPermissionManagement::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\Catalog\Api\DenyPermissionsForNewCategoryPlugin::class,
            [
                'configCategoryPermission' => $this->configCategoryPermission,
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
            ]
        );
    }

    /**
     * Test afterSave method.
     *
     * @return void
     */
    public function testAfterSave()
    {
        $categoryId = 1;
        $subject = $this->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPermissions'])
            ->getMockForAbstractClass();
        $this->configCategoryPermission->expects($this->once())->method('isConfigEnable')->willReturn(true);
        $category->expects($this->once())->method('getPermissions')->willReturn([]);
        $category->expects($this->once())->method('getId')->willReturn($categoryId);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setDenyPermissionsForCategory')
            ->with($categoryId);

        $this->assertEquals($category, $this->plugin->afterSave($subject, $category));
    }

    /**
     * Test afterSave method when disabled in config.
     *
     * @return void
     */
    public function testAfterSaveWhenDisabledInConfig()
    {
        $subject = $this->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $category = $this->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPermissions'])
            ->getMockForAbstractClass();
        $this->configCategoryPermission->expects($this->once())->method('isConfigEnable')->willReturn(false);
        $category->expects($this->once())->method('getPermissions')->willReturn([]);
        $this->catalogPermissionManagement->expects($this->never())->method('setDenyPermissionsForCategory');

        $this->assertEquals($category, $this->plugin->afterSave($subject, $category));
    }
}
