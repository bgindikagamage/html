<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\CatalogPermissions;

/**
 * Unit test for Magento\SharedCatalog\Plugin\CatalogPermissions\OpenTabPlugin.
 */
class OpenTabPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $status;

    /**
     * @var \Magento\SharedCatalog\Plugin\CatalogPermissions\OpenTabPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->status = $this->getMockBuilder(\Magento\SharedCatalog\Api\StatusInfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\CatalogPermissions\OpenTabPlugin::class,
            [
                'request' => $this->request,
                'status' => $this->status,
            ]
        );
    }

    /**
     * Test for method afterPrepareMeta for old category.
     */
    public function testAfterGetConfigJsonWithCategoryId()
    {
        $subject = $this
            ->getMockBuilder(\Magento\Catalog\Model\Category\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = [];
        $this->request->expects($this->once())->method('getParam')->willReturn(1);

        $this->assertEquals($result, $this->plugin->afterPrepareMeta($subject, $result));
    }

    /**
     * Test for method afterPrepareMeta for new category with disable shared catalog.
     */
    public function testAfterGetConfigJsonWithDisableShared()
    {
        $subject = $this
            ->getMockBuilder(\Magento\Catalog\Model\Category\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = [];
        $this->request->expects($this->once())->method('getParam')->willReturn(null);
        $this->status->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn([]);

        $this->assertEquals($result, $this->plugin->afterPrepareMeta($subject, $result));
    }

    /**
     * Test for method afterPrepareMeta for new category.
     */
    public function testAfterGetConfigJsonWithoutCategory()
    {
        $subject = $this
            ->getMockBuilder(\Magento\Catalog\Model\Category\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = [];
        $this->request->expects($this->once())->method('getParam')->willReturn(null);
        $this->status->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn([1]);
        $expected = [];
        $expected['category_permissions']['arguments']['data']['config']['opened'] = true;

        $this->assertEquals($expected, $this->plugin->afterPrepareMeta($subject, $result));
    }
}
