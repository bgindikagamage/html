<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Test\Unit\Model\Sorting;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DB\Select;

class SpecialPriceTopTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Sorting\SpecialPriceTop
     */
    private $model;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            \Magento\VisualMerchandiser\Model\Sorting\SpecialPriceTop::class,
            []
        );
    }

    public function testSort()
    {
        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->getMockForAbstractClass();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->any())->method('getConnection')->willReturn($connection);
        $collectionMock->expects($this->any())->method('getSelect')->willReturn($select);
        $select->expects($this->once())->method('getPart')->willReturn([]);
        $connection->expects($this->once())->method('getLeastSql');
        $connection->expects($this->exactly(2))->method('getCheckSql');
        $select->expects($this->once())->method('distinct')->with('entity_id')->willReturnSelf();
        $select->expects($this->once())->method('reset')->with(Select::ORDER)->willReturnSelf();
        $select->expects($this->once())->method('order')->willReturnSelf();
        $select->expects($this->once())->method('joinLeft')->willReturnSelf();
        $this->model->sort($collectionMock);
    }
}
