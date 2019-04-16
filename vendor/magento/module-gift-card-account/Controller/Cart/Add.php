<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Controller\Cart;

class Add extends \Magento\Checkout\Controller\Cart
{
    /**
     * Add Gift Card to current quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (isset($data['giftcard_code'])) {
            $code = $data['giftcard_code'];
            try {
                if (strlen($code) > \Magento\GiftCardAccount\Helper\Data::GIFT_CARD_CODE_MAX_LENGTH) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the gift card code.'));
                }
                $this->_objectManager->create(
                    \Magento\GiftCardAccount\Model\Giftcardaccount::class
                )->loadByCode(
                    $code
                )->addToCart();
                $this->messageManager->addSuccess(
                    __(
                        'Gift Card "%1" was added.',
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($code)
                    )
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We cannot apply this gift card.'));
            }
        }

        return $this->_goBack();
    }
}
