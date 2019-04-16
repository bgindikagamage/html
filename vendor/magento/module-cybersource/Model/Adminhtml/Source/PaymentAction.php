<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 *
 * Authorize.net Payment Action Dropdown source
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize Only')
            ]
        ];
    }
}
