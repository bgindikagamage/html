<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class PaymentTokenBuilder
 */
class PaymentTokenBuilder implements BuilderInterface
{
    const PAYMENT_TOKEN = 'payment_token';

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        return [
            self::PAYMENT_TOKEN => $paymentDO->getPayment()
                ->getAdditionalInformation(self::PAYMENT_TOKEN)
        ];
    }
}
