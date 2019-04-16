<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Controller\Cart;

class QuickCheck extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Check a gift card account availability
     *
     * @return void
     */
    public function execute()
    {
        /* @var $card \Magento\GiftCardAccount\Model\Giftcardaccount */
        $card = $this->_objectManager->create(
            \Magento\GiftCardAccount\Model\Giftcardaccount::class
        )->loadByCode(
            $this->getRequest()->getParam('giftcard_code', '')
        );
        $this->_coreRegistry->register('current_giftcardaccount', $card);
        try {
            $card->isValid(true, true, true, false);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $card->unsetData();
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
