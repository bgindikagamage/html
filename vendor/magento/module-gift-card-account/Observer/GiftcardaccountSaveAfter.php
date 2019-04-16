<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

class GiftcardaccountSaveAfter implements ObserverInterface
{
    /**
     * Gift card account history
     *
     * @var \Magento\GiftCardAccount\Model\History
     */
    protected $giftCAHistory = null;

    /**
     * @param \Magento\GiftCardAccount\Model\History $giftCAHistory
     */
    public function __construct(
        \Magento\GiftCardAccount\Model\History $giftCAHistory
    ) {
        $this->giftCAHistory = $giftCAHistory;
    }

    /**
     * Save history on gift card account model save event
     * used for event: magento_giftcardaccount_save_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $gca = $observer->getEvent()->getGiftcardaccount();

        if ($gca->hasHistoryAction()) {
            $this->giftCAHistory->setGiftcardaccount($gca)->save();
        }

        return $this;
    }
}
