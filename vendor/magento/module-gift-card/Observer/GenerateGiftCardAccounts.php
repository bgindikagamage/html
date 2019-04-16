<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;

/**
 * Gift cards generator observer called on order and invoice after save
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenerateGiftCardAccounts implements ObserverInterface
{
    /**
     * Gift card data
     *
     * @var \Magento\GiftCard\Helper\Data
     */
    protected $giftCardData = null;

    /**
     * Scope config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Url model
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlModel;

    /**
     * Order Repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\UrlInterface $urlModel
     * @param \Magento\GiftCard\Helper\Data $giftCardData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $urlModel,
        \Magento\GiftCard\Helper\Data $giftCardData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
        $this->transportBuilder = $transportBuilder;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->urlModel = $urlModel;
        $this->giftCardData = $giftCardData;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
    }

    /**
     * Generate gift card accounts after order save
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // sales_order_invoice_save_after & sales_order_save_after
        $eventName = $observer->getEvent()->getName();

        /** @var \Magento\Sales\Model\Order $order */
        if ($order = $this->resolveOrder($observer->getEvent())) {
            $requiredStatus = $this->scopeConfig->getValue(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_ORDER_ITEM_STATUS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStore()
            );
            $loadedInvoices = [];

            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            foreach ($order->getAllItems() as $orderItem) {
                if ($orderItem->getProductType() == Giftcard::TYPE_GIFTCARD) {
                    $qty = 0;
                    $options = $orderItem->getProductOptions();

                    switch ($requiredStatus) {
                        case \Magento\Sales\Model\Order\Item::STATUS_INVOICED:
                            if ($eventName == 'sales_order_invoice_save_after') {
                                $paidInvoiceItems = isset(
                                    $options['giftcard_paid_invoice_items']
                                ) ? $options['giftcard_paid_invoice_items'] : [];
                                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                                $invoice = $observer->getEvent()->getInvoice();
                                // find invoice for this order item
                                foreach ($invoice->getItems() as $invoiceItem) {
                                    $invoiceId = $invoice->getId();
                                    if (isset($loadedInvoices[$invoiceId])) {
                                        $invoice = $loadedInvoices[$invoiceId];
                                    } else {
                                        $loadedInvoices[$invoiceId] = $invoice;
                                    }
                                    // check, if this order item has been paid
                                    if ($invoice->getState() == \Magento\Sales\Model\Order\Invoice::STATE_PAID
                                        && !in_array($orderItem->getId(), $paidInvoiceItems)
                                    ) {
                                        $qty += $invoiceItem->getQty();
                                        $paidInvoiceItems[] = $orderItem->getId();
                                    }
                                }
                                $options['giftcard_paid_invoice_items'] = $paidInvoiceItems;
                            }
                            break;
                        case \Magento\Sales\Model\Order\Item::STATUS_PENDING:
                            if ($eventName == 'sales_order_save_after') {
                                $qty = $orderItem->getQtyOrdered();
                                if (isset($options['giftcard_created_codes'])) {
                                    $qty -= count($options['giftcard_created_codes']);
                                }
                                break;
                            }
                    }

                    $hasFailedCodes = false;
                    if ($qty > 0) {
                        $isRedeemable = 0;
                        $option = $orderItem->getProductOptionByCode('giftcard_is_redeemable');
                        if ($option) {
                            $isRedeemable = $option;
                        }

                        $lifetime = 0;
                        $option = $orderItem->getProductOptionByCode('giftcard_lifetime');
                        if ($option) {
                            $lifetime = $option;
                        }

                        $amount = $orderItem->getBasePrice();
                        $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();

                        $data = new \Magento\Framework\DataObject();
                        $data->setWebsiteId(
                            $websiteId
                        )->setAmount(
                            $amount
                        )->setLifetime(
                            $lifetime
                        )->setIsRedeemable(
                            $isRedeemable
                        )->setOrderItem(
                            $orderItem
                        );

                        $codes = isset($options['giftcard_created_codes']) ? $options['giftcard_created_codes'] : [];
                        $goodCodes = 0;
                        for ($i = 0; $i < $qty; $i++) {
                            try {
                                $code = new \Magento\Framework\DataObject();
                                $this->eventManager->dispatch(
                                    'magento_giftcardaccount_create',
                                    ['request' => $data, 'code' => $code]
                                );
                                $codes[] = $code->getCode();
                                $goodCodes++;
                            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                                $hasFailedCodes = true;
                                $codes[] = null;
                            }
                        }
                        if ($goodCodes && $orderItem->getProductOptionByCode('giftcard_recipient_email')) {
                            $sender = $orderItem->getProductOptionByCode('giftcard_sender_name');
                            $senderName = $orderItem->getProductOptionByCode('giftcard_sender_name');
                            $senderEmail = $orderItem->getProductOptionByCode('giftcard_sender_email');
                            if ($senderEmail) {
                                $sender = "{$sender} <{$senderEmail}>";
                            }

                            /** @var \Magento\GiftCard\Block\Generated $codeList */
                            $codeList = $this->giftCardData->getEmailGeneratedItemsBlock()->setCodes(
                                $codes
                            )->setArea(
                                \Magento\Framework\App\Area::AREA_FRONTEND
                            )->setIsRedeemable(
                                $isRedeemable
                            )->setStore(
                                $this->storeManager->getStore($order->getStoreId())
                            );
                            $balance = $this->localeCurrency->getCurrency(
                                $this->storeManager->getStore($order->getStoreId())->getBaseCurrencyCode()
                            )->toCurrency(
                                $amount
                            );

                            $templateData = [
                                'name' => $orderItem->getProductOptionByCode('giftcard_recipient_name'),
                                'email' => $orderItem->getProductOptionByCode('giftcard_recipient_email'),
                                'sender_name_with_email' => $sender,
                                'sender_name' => $senderName,
                                'gift_message' => $orderItem->getProductOptionByCode('giftcard_message'),
                                'giftcards' => $codeList->toHtml(),
                                'balance' => $balance,
                                'is_multiple_codes' => 1 < $goodCodes,
                                'store' => $order->getStore(),
                                'store_name' => $order->getStore()->getName(),
                                'is_redeemable' => $isRedeemable,
                            ];

                            $this->sendGiftcardItemEmail(
                                $orderItem->getProductOptionByCode('giftcard_email_template'),
                                $order->getStore()->getId(),
                                $orderItem->getProductOptionByCode('giftcard_recipient_email'),
                                $orderItem->getProductOptionByCode('giftcard_recipient_name'),
                                $templateData
                            );
                            $options['email_sent'] = 1;
                        }
                        $options['giftcard_created_codes'] = $codes;
                        $orderItem->setProductOptions($options);
                        // order item could be saved later after this order even
                        if ($orderItem->getId()) {
                            $orderItem->save();
                        }
                    }
                    if ($hasFailedCodes) {
                        $url = $this->urlModel->getUrl('adminhtml/giftcardaccount');
                        $message = __(
                            'Some gift card accounts were not created properly. '
                            . 'You can create gift card accounts manually <a href="%1">here</a>.',
                            $url
                        );

                        $this->messageManager->addErrorMessage($message);
                    }
                }
            }
        }
    }

    /**
     * Resolve orderItem from event, invoice or repository
     *
     * @param \Magento\Framework\Event $event
     * @return \Magento\Sales\Api\Data\OrderInterface|void
     */
    private function resolveOrder(\Magento\Framework\Event $event)
    {
        $eventName = $event->getName();
        if ($eventName == 'sales_order_invoice_save_after') {
            $invoice = $event->getInvoice();
            /** @var \Magento\Sales\Model\Order $invoice */
            if (empty($invoice->getOrder())) {
                //case where we don't have a order item id loaded
                return $this->orderRepository->get($invoice->getId());
            } else {
                //case where order item was loaded but will be saved after invoice item save
                return $invoice->getOrder();
            }
        } elseif ($eventName == 'sales_order_save_after') {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            return $event->getOrder();
        }
    }

    /**
     * @param string $template
     * @param int $storeId
     * @param string $recipientAddress
     * @param string $recipientName
     * @param array $templateData
     * @return void
     */
    private function sendGiftcardItemEmail($template, $storeId, $recipientAddress, $recipientName, $templateData)
    {
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $template
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId,
            ]
        )->setTemplateVars(
            $templateData
        )->setFrom(
            $this->scopeConfig->getValue(
                \Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->addTo(
            $recipientAddress,
            $recipientName
        )->getTransport();

        $transport->sendMessage();
    }
}
