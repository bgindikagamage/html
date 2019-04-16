<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel;

/**
 * Unit test for Magento\SharedCatalog\Model\ResourceModel\Permission class.
 */
class PermissionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resources;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resources = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\ResourceModel\Permission::class,
            ['_resources' => $this->resources]
        );
    }

    /**
     * Test addPermissions method.
     *
     * @return void
     */
    public function testAddPermissions()
    {
        $data = [
            'customer_group_id' => 3,
            'category_id' => 3,
            'website_id' => 1,
            'permission' => 1,
        ];
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->resources->expects($this->once())->method('getConnection')->with('default')->willReturn($connection);
        $this->resources->expects($this->once())->method('getTableName')
            ->with(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME)
            ->willReturn(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME);
        $connection->expects($this->once())->method('insertOnDuplicate')
            ->with(
                \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME,
                $data
            )->willReturn(1);
        $this->model->addPermissions($data);
    }
}
