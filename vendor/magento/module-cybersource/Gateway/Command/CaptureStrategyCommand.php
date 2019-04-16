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
use Magento\Sales\Model\Order;
use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;

/**
 * Makes capture or sale transaction.
 */
class CaptureStrategyCommand implements CommandInterface
{
    /**
     * Secure Acceptance sale command name
     */
    const SECURE_ACCEPTANCE_SALE = 'secure_acceptance_sale';

    /**
     * Simple order capture command name
     */
    const SIMPLE_ORDER_CAPTURE = 'simple_order_capture';

    /**
     * Simple order subscription command name
     */
    const SIMPLE_ORDER_SUBSCRIPTION = 'simple_order_subscription';

    /**
     * Simple order sale command name
     */
    const SIMPLE_ORDER_SALE = 'simple_order_sale';

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
     * Executes capture command.
     *
     * If authorization transaction is present then capture performs using Simple Order API.
     * Usually, sale transaction is performed using Secure Acceptance Silent Order.
     * But if Decision Manager triggers a fraud, payment token won't be returned.
     * So we should use Simple Order API for converting a transaction to a customer subscription.
     * Then sale transaction can be performed using Simple Order API and subscriptionId.
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Order\Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        if ($paymentInfo instanceof Order\Payment
            && $paymentInfo->getAuthorizationTransaction()
        ) {
            return $this->commandPool
                ->get(self::SIMPLE_ORDER_CAPTURE)
                ->execute($commandSubject);
        }

        $paymentToken = $paymentInfo->getAdditionalInformation(PaymentTokenBuilder::PAYMENT_TOKEN);
        $saleCommand = empty($paymentToken) ?
            $this->getSimpleOrderSaleCommand($commandSubject) : $this->getSecureAcceptanceSaleCommand();

        return $saleCommand->execute($commandSubject);
    }

    /**
     * Returns Secure Acceptance Silent Order sale command.
     *
     * @return CommandInterface
     * @see http://apps.cybersource.com/library/documentation/dev_guides/Secure_Acceptance_SOP/html/
     */
    private function getSecureAcceptanceSaleCommand()
    {
        return $this->commandPool->get(self::SECURE_ACCEPTANCE_SALE);
    }

    /**
     * Returns Simple Order API sale command.
     *
     * Converts a Transaction to a Customer Subscription and
     * returns Simple Order sale command.
     *
     * @param array $commandSubject
     * @return CommandInterface
     * @see https://www.cybersource.com/en-APAC/products/payment_processing/recurring_billing/
     * @see http://apps.cybersource.com/library/documentation/dev_guides/Simple_Order_API_Clients/Client_SDK_SO_API.pdf
     */
    private function getSimpleOrderSaleCommand(array $commandSubject)
    {
        $this->commandPool
            ->get(self::SIMPLE_ORDER_SUBSCRIPTION)
            ->execute($commandSubject);

        return $this->commandPool->get(self::SIMPLE_ORDER_SALE);
    }
}
