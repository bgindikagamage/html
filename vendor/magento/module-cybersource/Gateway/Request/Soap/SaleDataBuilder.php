<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\Soap;

class SaleDataBuilder extends AuthorizeDataBuilder
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        return array_merge(
            parent::build($buildSubject),
            [
                'ccCaptureService' => [
                    'run' => 'true'
                ]
            ]
        );
    }
}
