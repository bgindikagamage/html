<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\Observer;

class CreditmemoDataImportObserver implements ObserverInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CreditmemoDataImportObserver constructor.
     *
     * @param PriceCurrencyInterface $priceCurrency
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Sets refund flag for manual refund (with "Refund to Store Credit" input)
     * or for gift card if Store Credit is enabled and it was a Registered Customer
     * used for event: adminhtml_sales_order_creditmemo_register_before
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $input = $observer->getEvent()->getInput();
        $refundCustomerBalanceReturnEnable = !empty($input['refund_customerbalance_return_enable']);
        $refundCustomerBalanceAmount = !empty($input['refund_customerbalance_return'])
            ? $input['refund_customerbalance_return']
            : null;

        if ($refundCustomerBalanceReturnEnable && is_numeric($refundCustomerBalanceAmount)) {
            $refundCustomerBalanceAmount = max(
                0,
                min($creditmemo->getBaseCustomerBalanceReturnMax(), $refundCustomerBalanceAmount)
            );
            $this->prepareCreditmemoForRefund($creditmemo, $refundCustomerBalanceAmount);
        }

        if (!$refundCustomerBalanceReturnEnable) {
            $this->refundGiftCardAmmountToStoreCredit($creditmemo);
        }

        if (!empty($input['refund_customerbalance'])) {
            $creditmemo->setRefundCustomerBalance(true);
        }

        if (!empty($input['refund_real_customerbalance'])) {
            $creditmemo->setRefundRealCustomerBalance(true);
            $creditmemo->setPaymentRefundDisallowed(true);
        }
    }

    /**
     * If Store Credit is enabled and it is a Registered Customer,
     * gift card amount refunds to Store Credit.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    private function refundGiftCardAmmountToStoreCredit(Creditmemo $creditmemo)
    {
        $giftCardsAmount = $this->priceCurrency->round($creditmemo->getGiftCardsAmount());
        if (!$this->validateAmount($giftCardsAmount)) {
            return;
        }

        // Gets 'Enable Store Credit Functionality' flag from the Scope Config
        $customerBalanceIsEnabled = $this->scopeConfig->isSetFlag(
            'customer/magento_customerbalance/is_enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$creditmemo->getOrder()->getCustomerIsGuest() && $customerBalanceIsEnabled) {
            $this->prepareCreditmemoForRefund($creditmemo, $giftCardsAmount);
        }
    }

    /**
     * Sets refund flag to creditmemo based on user input or presents gift card in the order.
     *
     * @param Creditmemo $creditmemo
     * @param float $amount
     * @return void
     */
    private function prepareCreditmemoForRefund(Creditmemo $creditmemo, $amount)
    {
        if (!$this->validateAmount($amount)) {
            return;
        }

        $amount = $this->priceCurrency->round($amount);
        $creditmemo->setBsCustomerBalTotalRefunded($amount);

        $amount = $this->priceCurrency->round(
            $amount * $creditmemo->getOrder()->getBaseToOrderRate()
        );
        $creditmemo->setCustomerBalTotalRefunded($amount);
        //setting flag to make actual refund to customer balance after creditmemo save
        $creditmemo->setCustomerBalanceRefundFlag(true);
        //allow online refund
        $creditmemo->setPaymentRefundDisallowed(false);
    }

    /**
     * Validates amount for refund.
     *
     * @param float $amount
     * @return bool
     */
    private function validateAmount($amount)
    {
        return is_numeric($amount) && ($amount > 0);
    }
}
