<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Plugin\SalesRule\Observer;

use Magento\SalesRule\Observer\SalesOrderAfterPlaceObserver;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class SalesOrderAfterPlaceObserverPlugin
 */
class SalesOrderAfterPlaceObserverPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var string
     */
    private $originalAppliedRuleIds = '';

    /**
     * @var float
     */
    private $originalDiscountAmount = 0;

    /**
     * SalesOrderAfterPlaceObserverPlugin constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param SalesOrderAfterPlaceObserver $subject
     * @param \Closure $proceed
     * @param EventObserver $observer
     * @return SalesOrderAfterPlaceObserver
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SalesOrderAfterPlaceObserver $subject,
        \Closure $proceed,
        EventObserver $observer
    ) {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        $isDiscountChanges = $this->setRulesOnOrder($order);

        $result = $proceed($observer);

        if ($isDiscountChanges) {
            $order->setAppliedRuleIds($this->originalAppliedRuleIds);
            $order->setDiscountAmount($this->originalDiscountAmount);
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    private function setRulesOnOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $this->originalAppliedRuleIds = '';
        $this->originalDiscountAmount = 0;
        if ($order && $order->getQuoteId() && $order->getDiscountAmount() == 0) {
            try {
                $quote = $this->quoteRepository->get($order->getQuoteId(), ['*']);
                $negotiableQuote = $quote->getExtensionAttributes()
                && $quote->getExtensionAttributes()->getNegotiableQuote()
                    ? $quote->getExtensionAttributes()->getNegotiableQuote()
                    : null;
                if ($negotiableQuote && $negotiableQuote->getAppliedRuleIds()) {
                    $this->originalAppliedRuleIds = $order->getAppliedRuleIds();
                    $this->originalDiscountAmount = $order->getDiscountAmount();
                    $order->setAppliedRuleIds($negotiableQuote->getAppliedRuleIds());
                    $order->setDiscountAmount(1);
                    return true;
                }
            } catch (NoSuchEntityException $e) {
                //no log exception
            }
        }
        return false;
    }
}
