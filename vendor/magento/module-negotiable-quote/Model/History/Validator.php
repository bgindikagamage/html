<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\History;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Validator
 * @package Magento\NegotiableQuote\Model\History
 */
class Validator
{
    /**
     * Required fields
     *
     * @var array
     */
    protected $requiredFields = [
        'quote_id' => 'Negotiable quote ID',
        'author_id' => 'Author ID'
    ];

    /**
     * Validate method
     *
     * @param AbstractModel $object
     * @return array
     */
    public function validate(AbstractModel $object)
    {
        $warnings = [];
        foreach ($this->requiredFields as $code => $label) {
            if (!$object->hasData($code)) {
                $warnings[] = sprintf('%s is a required field', $label);
            }
        }
        return $warnings;
    }
}
