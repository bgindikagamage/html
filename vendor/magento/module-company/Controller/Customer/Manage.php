<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Manage.
 */
class Manage extends \Magento\Company\Controller\AbstractAction
{
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        $action = (int)$customerId ? 'save' : 'create';

        /** @var \Magento\Framework\Controller\Result\Forward $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $result->forward($action);
    }
}
