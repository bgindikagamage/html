<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Request\Soap\AuthorizeDataBuilder;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class SubscriptionIdHandler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (empty($response['paySubscriptionCreateReply'][AuthorizeDataBuilder::SUBSCRIPTION_ID])) {
            return;
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $paymentDO->getPayment()
            ->setAdditionalInformation(
                AuthorizeDataBuilder::SUBSCRIPTION_ID,
                $response['paySubscriptionCreateReply'][AuthorizeDataBuilder::SUBSCRIPTION_ID]
            );
    }
}
