<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\CatalogPermissions\Block;

/**
 * Unit test for Magento\SharedCatalog\Plugin\CatalogPermissions\Block\PermissionsForNewCategoryPlugin.
 */
class PermissionsForNewCategoryPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Model\PermissionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permissionFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializer;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $status;

    /**
     * @var \Magento\SharedCatalog\Plugin\CatalogPermissions\Block\PermissionsForNewCategoryPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->permissionFactory = $this->getMockBuilder(\Magento\CatalogPermissions\Model\PermissionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->status = $this->getMockBuilder(\Magento\SharedCatalog\Api\StatusInfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\CatalogPermissions\Block\PermissionsForNewCategoryPlugin::class,
            [
                'permissionFactory' => $this->permissionFactory,
                'serializer' => $this->serializer,
                'customerGroupManagement' => $this->customerGroupManagement,
                'status' => $this->status,
            ]
        );
    }

    /**
     * Test for method afterGetConfigJson for old category.
     */
    public function testAfterGetConfigJsonWithCategoryId()
    {
        $subject = $this
            ->getMockBuilder(\Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->atLeastOnce())->method('getCategoryId')->willReturn(1);
        $result = 'string';

        $this->assertEquals($result, $this->plugin->afterGetConfigJson($subject, $result));
    }

    /**
     * Test for method afterGetConfigJson for new category.
     */
    public function testAfterGetConfigJsonWithoutCategoryId()
    {
        $this->status->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn([1]);
        $subject = $this
            ->getMockBuilder(\Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->atLeastOnce())->method('getCategoryId')->willReturn(0);
        $group = 2;
        $result = 'encodedString';
        $resultEncoded = 'resultEncoded';
        $resultDecoded = ['permissions' => [], 'duplicate_message' => 'message'];
        $this->serializer->expects($this->once())->method('unserialize')->with($result)->willReturn($resultDecoded);
        $this->customerGroupManagement->expects($this->atLeastOnce())
            ->method('getSharedCatalogGroupIds')->willReturn([$group]);
        $permission = $this->getMockBuilder(\Magento\CatalogPermissions\Model\Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setCustomerGroupId',
                    'setGrantCatalogCategoryView',
                    'setGrantCatalogProductPrice',
                    'setGrantCheckoutItems',
                    'getData'
                ]
            )
            ->getMock();
        $this->permissionFactory->expects($this->once())->method('create')->willReturn($permission);
        $denyPermission = \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY;
        $permission->expects($this->once())->method('setCustomerGroupId')->with($group);
        $permission->expects($this->once())->method('setGrantCatalogCategoryView')->with($denyPermission);
        $permission->expects($this->once())->method('setGrantCatalogProductPrice')->with($denyPermission);
        $permission->expects($this->once())->method('setGrantCheckoutItems')->with($denyPermission);
        $permission->expects($this->once())->method('getData')->willReturn(['permission']);
        $resultDecoded['permissions']['permission1'] = ['permission'];
        $this->serializer->expects($this->once())->method('serialize')
            ->with($resultDecoded)->willReturn($resultEncoded);

        $this->assertEquals($resultEncoded, $this->plugin->afterGetConfigJson($subject, $result));
    }
}
