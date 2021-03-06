<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Controller\History;

/**
 * Class Index.
 */
class Index extends \Magento\CompanyCredit\Controller\AbstractAction
{
    /**
     * View company credit balance history.
     *
     * @return \Magento\Framework\View\Result\Page
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Company Credit'));
        $navigationBlock = $resultPage->getLayout()
            ->getBlock('customer-account-navigation-company-credit-history-link');

        if ($navigationBlock) {
            $navigationBlock->setActive('company_credit/history');
        }

        return $resultPage;
    }
}
