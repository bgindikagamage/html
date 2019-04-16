<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Unit\Model\Company\Source;

use Magento\Company\Model\Company\Source\Gender;

/**
 * Class GenderTest.
 */
class GenderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Company\Model\Company\Source\Gender
     */
    protected $gender;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->attributeFactory = $this->createPartialMock(
            \Magento\Eav\Model\Entity\AttributeFactory::class,
            [
                'create'
            ]
        );

        $this->gender = new Gender($this->attributeFactory);
    }

    public function testToOptionArray()
    {
        $label = 'label';
        $value = 'value';
        $result = [['label' => $label, 'value' => $value]];
        $attribute = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute::class,
            [
                'getOptions',
                'loadByCode',
            ]
        );
        $option = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute\Option::class,
            [
                'getLabel',
                'getValue',
            ]
        );
        $this->attributeFactory->expects($this->once())->method('create')->willReturn($attribute);
        $attribute->expects($this->once())->method('loadByCode')->with(Gender::ENTITY_TYPE_CUSTOMER, 'gender');
        $attribute->expects($this->once())->method('getOptions')->willReturn([$option]);
        $option->expects($this->once())->method('getLabel')->willReturn($label);
        $option->expects($this->once())->method('getValue')->willReturn($value);
        $this->assertEquals($this->gender->toOptionArray(), $result);
    }
}
