<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Unit\Model\ResourceModel\Company\Grid;

/**
 * Class CollectionTest.
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Company\Model\ResourceModel\Company\Grid\Collection
     */
    protected $companyGrid;

    /**
     * @var \Magento\Framework\DB\Select
     */
    protected $select;

    protected function setUp()
    {
        $this->select = $this->createPartialMock(
            \Magento\Framework\DB\Select::class,
            ['where']
        );

        $this->companyGrid = $this->createPartialMock(
            \Magento\Company\Model\ResourceModel\Company\Grid\Collection::class,
            ['getSelect']
        );
        $this->companyGrid->expects($this->any())->method('getSelect')->willReturn($this->select);
    }

    /**
     * Create save
     *
     */
    public function testAddFieldToFilter()
    {
        $result = '';
        $whereCallback = function ($resultCondition) use (&$result) {
            $result = $resultCondition;
        };
        $this->select->expects($this->any())->method('where')->will($this->returnCallback($whereCallback));

        $this->companyGrid->addFieldToFilter('region');
        $this->assertEquals('main_table.region like \'\' OR directory_country_region.default_name like \'\'', $result);
    }
}
