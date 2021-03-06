<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Adminhtml\Report\Customer\Wishlist;

use Magento\Framework\Controller\ResultFactory;

class Wishlist extends \Magento\MultipleWishlist\Controller\Adminhtml\Report\Customer\Wishlist
{
    /**
     * Wishlist view action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_MultipleWishlist::report_customers_wishlist');
        $resultPage->addBreadcrumb(__('Reports'), __('Reports'));
        $resultPage->addBreadcrumb(__('Customers'), __('Customers'));

        $resultPage->getConfig()->getTitle()->prepend(__("Customer Wish List Report"));
        return $resultPage;
    }
}
