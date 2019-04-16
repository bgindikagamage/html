<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Company\Source;

/**
 * Class Gender
 */
class Gender implements \Magento\Framework\Data\OptionSourceInterface
{
    const ENTITY_TYPE_CUSTOMER = 'customer';

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     */
    public function __construct(\Magento\Eav\Model\Entity\AttributeFactory $attributeFactory)
    {
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * Get options
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $result = [];
        $attribute = $this->attributeFactory->create();
        $attribute->loadByCode(self::ENTITY_TYPE_CUSTOMER, 'gender');
        $options = $attribute->getOptions();
        foreach ($options as $item) {
            $result[] = ['label' => $item->getLabel(), 'value' => $item->getValue()];
        }
        return $result;
    }
}
