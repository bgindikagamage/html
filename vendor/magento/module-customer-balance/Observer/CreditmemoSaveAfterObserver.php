<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ObserverInterface;

/**
 * Customer balance observer
 */
class CreditmemoSaveAfterObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     */
    public function __construct(
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
        $this->_customerBalanceData = $customerBalanceData;
    }

    /**
     * Refund process
     * used for event: sales_order_creditmemo_save_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        if ($creditmemo->getAutomaticallyCreated()) {
            if ($this->_customerBalanceData->isAutoRefundEnabled()) {
                $creditmemo->setCustomerBalanceRefundFlag(
                    true
                )->setCustomerBalTotalRefunded(
                    $creditmemo->getCustomerBalanceAmount()
                )->setBsCustomerBalTotalRefunded(
                    $creditmemo->getBaseCustomerBalanceAmount()
                );
            } else {
                return $this;
            }
        }

        // values may be not equal due to precison of decimal numbers
        // (http://php.net/manual/en/language.types.float.php)
        $floatMathPrecision = 1e-10;
        if ($creditmemo->getCustomerBalTotalRefunded() - $creditmemo->getCustomerBalanceReturnMax()
            >= $floatMathPrecision
        ) {
            throw new LocalizedException(__('You can\'t use more store credit than the order amount.'));
        }
        //doing actual refund to customer balance if user have submitted refund form
        if ($creditmemo->getCustomerBalanceRefundFlag() && $creditmemo->getBsCustomerBalTotalRefunded()) {
            $order->setBsCustomerBalTotalRefunded(
                $order->getBsCustomerBalTotalRefunded() + $creditmemo->getBsCustomerBalTotalRefunded()
            );
            $order->setCustomerBalTotalRefunded(
                $order->getCustomerBalTotalRefunded() + $creditmemo->getCustomerBalTotalRefunded()
            );

            $websiteId = $this->_storeManager->getStore($order->getStoreId())->getWebsiteId();

            $this->_balanceFactory->create()->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $websiteId
            )->setAmountDelta(
                $creditmemo->getBsCustomerBalTotalRefunded()
            )->setHistoryAction(
                \Magento\CustomerBalance\Model\Balance\History::ACTION_REFUNDED
            )->setOrder(
                $order
            )->setCreditMemo(
                $creditmemo
            )->save();
        }

        return $this;
    }
}
