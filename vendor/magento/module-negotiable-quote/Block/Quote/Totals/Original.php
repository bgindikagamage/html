<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Block\Quote\Totals;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals\AbstractTotals;

/**
 * Class Original.
 */
class Original extends AbstractTotals
{
    /**
     * @var string
     */
    protected $code = 'catalog_price';

    /**
     * Display prices.
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    public function displayPrices($price = null, $currency = null)
    {
        $total = $this->getTotal();
        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            isset($currency) ? $currency : $total->getBaseCurrency()
        );
    }
}
