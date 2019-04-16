<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;

/**
 * Makes authorization transaction.
 */
class AuthorizeStrategyCommand implements CommandInterface
{
    /**
     * Secure Acceptance authorize command name
     */
    const SECURE_ACCEPTANCE_AUTHORIZE = 'secure_acceptance_authorize';

    /**
     * Simple order authorize command name
     */
    const SIMPLE_ORDER_AUTHORIZE = 'simple_order_authorize';

    /**
     * Simple order subscription command name
     */
    const SIMPLE_ORDER_SUBSCRIPTION = 'simple_order_subscription';

    /**
     * @var Command\CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param Command\CommandPoolInterface $commandPool
     */
    public function __construct(
        Command\CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * Executes authorization command.
     *
     * Usually, authorization performs using Secure Acceptance Silent Order.
     * But if Decision Manager triggers a fraud, payment token won't be returned.
     * So we should use Simple Order API for converting a transaction to a customer subscription.
     * Then authorization can be performed using Simple Order API and subscriptionId.
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $paymentInfo = $paymentDO->getPayment();

        $paymentToken = $paymentInfo->getAdditionalInformation(PaymentTokenBuilder::PAYMENT_TOKEN);
        $authorizeCommand = empty($paymentToken) ?
            $this->getSimpleOrderAuthorizeCommand($commandSubject) : $this->getSecureAcceptanceAuthorizeCommand();

        return $authorizeCommand->execute($commandSubject);
    }

    /**
     * Returns Secure Acceptance Silent Order authorize command.
     *
     * @return CommandInterface
     * @see http://apps.cybersource.com/library/documentation/dev_guides/Secure_Acceptance_SOP/html/
     */
    private function getSecureAcceptanceAuthorizeCommand()
    {
        return $this->commandPool->get(self::SECURE_ACCEPTANCE_AUTHORIZE);
    }

    /**
     * Returns Simple Order API authorize command.
     *
     * Converts a Transaction to a Customer Subscription and
     * returns Simple Order authorize command.
     *
     * @param array $commandSubject
     * @return CommandInterface
     * @see https://www.cybersource.com/en-APAC/products/payment_processing/recurring_billing/
     * @see http://apps.cybersource.com/library/documentation/dev_guides/Simple_Order_API_Clients/Client_SDK_SO_API.pdf
     */
    private function getSimpleOrderAuthorizeCommand(array $commandSubject)
    {
        $this->commandPool
            ->get(self::SIMPLE_ORDER_SUBSCRIPTION)
            ->execute($commandSubject);

        return $this->commandPool->get(self::SIMPLE_ORDER_AUTHORIZE);
    }
}
