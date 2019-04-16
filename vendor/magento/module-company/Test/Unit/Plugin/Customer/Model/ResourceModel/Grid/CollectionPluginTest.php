<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Unit\Plugin\Customer\Model\ResourceModel\Grid;

/**
 * Unit test for Magento\Company\Plugin\Customer\Model\ResourceModel\Grid\CollectionPlugin class.
 */
class CollectionPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Company\Plugin\Customer\Model\ResourceModel\Grid\CollectionPlugin
     */
    private $collectionPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->collectionPlugin = $objectManagerHelper->getObject(
            \Magento\Company\Plugin\Customer\Model\ResourceModel\Grid\CollectionPlugin::class
        );
    }

    /**
     * Test beforeLoadWithFilter method.
     *
     * @return void
     */
    public function testAfterGetCompanyResultData()
    {
        $customerCollection = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Grid\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerCollection->expects($this->at(0))
            ->method('getSelect')
            ->willReturn($select);
        $customerCollection->expects($this->at(1))
            ->method('getTable')
            ->with('company_advanced_customer_entity')
            ->willReturn('company_advanced_customer_entity');
        $select->expects($this->at(0))
            ->method('joinLeft')
            ->with(
                ['company_customer' => 'company_advanced_customer_entity'],
                'company_customer.customer_id = main_table.entity_id',
                ['company_customer.status']
            )
            ->willReturnSelf();

        $customerCollection->expects($this->at(2))
            ->method('getSelect')
            ->willReturn($select);
        $customerCollection->expects($this->at(3))
            ->method('getTable')
            ->with('company')
            ->willReturn('company');
        $select->expects($this->at(1))
            ->method('joinLeft')
            ->with(
                ['company' => 'company'],
                'company.entity_id = company_customer.company_id',
                ['company.company_name']
            )
            ->willReturnSelf();

        $customerCollection->expects($this->at(4))
            ->method('getSelect')
            ->willReturn($select);
        $columns = [
            'customer_type' => new \Zend_Db_Expr(
                '(IF(company_customer.company_id > 0,'
                . ' IF(company_customer.customer_id = company.super_user_id, "0", "1"), "2"))'
            )
        ];
        $select->expects($this->at(2))
            ->method('columns')
            ->with($columns)
            ->willReturnSelf();
        $this->collectionPlugin->beforeLoadWithFilter($customerCollection);
    }
}
