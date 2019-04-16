<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\Validator;

/**
 * Interface for data validation.
 */
interface ValidatorInterface
{
    /**
     * Validate $data.
     *
     * @param array $data
     * @return \Magento\NegotiableQuote\Model\Validator\ValidatorResult
     */
    public function validate(array $data);
}
