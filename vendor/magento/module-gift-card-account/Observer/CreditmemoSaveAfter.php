<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCardAccount\Helper\Data as GiftCardAccountDataHelper;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Framework\Phrase;

/**
 * GiftCardAccount Module Observer.
 * used for event: sales_order_creditmemo_save_after
 */
class CreditmemoSaveAfter implements ObserverInterface
{
    /**
     * @var string
     */
    private static $messageRefundToGiftCard = "We refunded %1 to Gift Card (%2)";

    /**
     * @var string
     */
    private static $messageRefundToStoryCredit = "We refunded %1 to Store Credit from Gift Card (%2)";

    /**
     * @var GiftCardAccountDataHelper
     */
    private $giftCardAccountHelper;

    /**
     * @var GiftcardaccountFactory
     */
    private $giftCardAccountFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $orderStatusHistoryRepository;

    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * CreditmemoSaveAfter constructor.
     *
     * @param GiftCardAccountDataHelper             $giftCardAccountHelper
     * @param GiftcardaccountFactory                $giftCardAccountFactory
     * @param ScopeConfigInterface                  $scopeConfig
     * @param GiftCardAccountRepositoryInterface    $giftCardAccountRepository
     * @param HistoryFactory                        $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        GiftCardAccountDataHelper $giftCardAccountHelper,
        GiftcardaccountFactory $giftCardAccountFactory,
        ScopeConfigInterface $scopeConfig,
        GiftCardAccountRepositoryInterface $giftCardAccountRepository,
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->giftCardAccountHelper = $giftCardAccountHelper;
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->scopeConfig = $scopeConfig;
        $this->giftCardAccountRepository = $giftCardAccountRepository;
        $this->historyFactory = $historyFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * Refunds process.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if (!$this->refundToCustomerBalanceExpected($creditmemo)) {
            $this->refundToGiftCardAccount($creditmemo);

            return;
        }

        $this->addRefundToStoryCreditComments($creditmemo);
    }

    /**
     * Refunds Gift Card amount to Store Credit comments.
     * Customer is not guest and Credit Store enabled.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    private function addRefundToStoryCreditComments(Creditmemo $creditmemo)
    {
        /** @var Order $order */
        $order = $creditmemo->getOrder();
        $cards = $this->giftCardAccountHelper->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                /** @var Giftcardaccount $account */
                $giftCardCode = $card[Giftcardaccount::CODE];
                $giftCardAmount = $card[Giftcardaccount::AMOUNT];
                $comment = __(
                    self::$messageRefundToStoryCredit,
                    $order->getBaseCurrency()->formatTxt($giftCardAmount),
                    $giftCardCode
                );
                $this->addCommentToHistory($order->getId(), $comment);
            }
        }
    }

    /**
     * Checks conditions for refund to Gift Card Account.
     *
     * @param Creditmemo $creditmemo
     * @return bool
     */
    private function refundToCustomerBalanceExpected(Creditmemo $creditmemo)
    {
        /** @var Order $order */
        $order = $creditmemo->getOrder();
        if (!$creditmemo->getCustomerBalanceRefundFlag() || $order->getCustomerIsGuest()) {
            return false;
        }

        // Gets 'Enable Store Credit Functionality' flag from the Scope Config.
        $customerBalanceIsEnabled = $this->scopeConfig->isSetFlag(
            'customer/magento_customerbalance/is_enabled',
            ScopeInterface::SCOPE_STORE
        );

        return $customerBalanceIsEnabled;
    }

    /**
     * Refunds to Giftcardaccount if customer is not guest or Credit Store disabled.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    private function refundToGiftCardAccount(Creditmemo $creditmemo)
    {
        /** @var Order $order */
        $order = $creditmemo->getOrder();
        $cards = $this->giftCardAccountHelper->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                /** @var Giftcardaccount $account */
                $account = $this->giftCardAccountFactory->create();
                $giftCardCode = $card[Giftcardaccount::CODE];
                $giftCardAmount = $card[Giftcardaccount::AMOUNT];
                $account->loadByCode($giftCardCode);
                // The gift card was removed manually or by cron job
                if (!$account->getId()) {
                    $account = $this->createGiftCardAccount($order, $card);
                }

                $account->revert($giftCardAmount);
                $comment = __(
                    self::$messageRefundToGiftCard,
                    $order->getBaseCurrency()->formatTxt($giftCardAmount),
                    $giftCardCode
                );
                $this->addCommentToHistory($order->getId(), $comment);
                $this->giftCardAccountRepository->save($account);
            }
        }
    }

    /**
     * Creates and initializes new gift card account.
     *
     * @param Order $order
     * @param array $card
     * @return Giftcardaccount
     */
    private function createGiftCardAccount(Order $order, array $card)
    {
        /** @var Giftcardaccount $account */
        $newAccount = $this->giftCardAccountFactory
            ->create()
            ->setStatus(Giftcardaccount::STATUS_ENABLED)
            ->setWebsiteId($order->getStore()->getWebsiteId())
            ->setCode($card[Giftcardaccount::CODE])
            ->setBalance(0)
            ->setOrder($order);

        $this->giftCardAccountRepository->save($newAccount);

        return $newAccount;
    }

    /**
     * Adds new comment in the order's history.
     *
     * @param string $orderId
     * @param Phrase $comment
     * @return void
     */
    private function addCommentToHistory($orderId, Phrase $comment)
    {
        $history = $this->historyFactory->create();
        $history->setParentId($orderId)
            ->setComment($comment)
            ->setEntityName('order')
            ->setStatus(Order::STATE_CLOSED)
            ->save();

        $this->orderStatusHistoryRepository->save($history);
    }
}
