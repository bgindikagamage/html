<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Plugin\Quote\Model;

use Magento\Quote\Model\Quote;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Plugin for quote on adminhtml area.
 */
class QuoteAdminhtmlPlugin
{
    /**
     * Return currency and rate for quote.
     *
     * @param Quote $subject
     * @param \Closure $proceed
     * @return Quote
     */
    public function aroundBeforeSave(Quote $subject, \Closure $proceed)
    {
        $currencyCode = $subject->getQuoteCurrencyCode();
        $currencyRate = $subject->getBaseToQuoteRate();
        $currencyCodeBase = $subject->getBaseCurrencyCode();
        $result = $proceed();
        $blockedStatuses = [NegotiableQuoteInterface::STATUS_CLOSED, NegotiableQuoteInterface::STATUS_ORDERED];
        if ($subject->getExtensionAttributes() != null
            && $subject->getExtensionAttributes()->getNegotiableQuote() != null
            && $subject->getExtensionAttributes()->getNegotiableQuote()->getIsRegularQuote() != null
            && in_array($subject->getExtensionAttributes()->getNegotiableQuote()->getStatus(), $blockedStatuses)
        ) {
            if ($currencyCode != $subject->getQuoteCurrencyCode()) {
                $subject->setQuoteCurrencyCode($currencyCode);
            }
            if ($currencyRate != $subject->getBaseToQuoteRate()) {
                $subject->setBaseToQuoteRate($currencyRate);
            }
            if ($currencyCodeBase != $subject->getBaseCurrencyCode()) {
                $subject->setBaseCurrencyCode($currencyCodeBase);
            }
        }

        return $result;
    }

    /**
     * Check is currency available for store.
     *
     * @param \Magento\Store\Model\Store $store
     * @param string $code
     * @return bool
     */
    private function isCurrencyAvailable(\Magento\Store\Model\Store $store, $code)
    {
        $allowedCurrency = $store->getAvailableCurrencyCodes(true);
        return in_array($code, $allowedCurrency) && $store->getBaseCurrency()->getRate($code);
    }

    /**
     * Set quote currency as current currency in store.
     *
     * @param Quote $subject
     * @param \Magento\Store\Model\Store $result
     * @return \Magento\Store\Model\Store
     */
    public function afterGetStore(Quote $subject, \Magento\Store\Model\Store $result)
    {
        if ($this->isCurrencyAvailable($result, $subject->getQuoteCurrencyCode())) {
            $result->setCurrentCurrencyCode($subject->getQuoteCurrencyCode());
        }
        return $result;
    }
}
