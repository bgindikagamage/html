<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Plugin\Customer\Controller\Address;

use Magento\Customer\Controller\Address\NewAction;

/**
 * Class NewActionPlugin.
 */
class NewActionPlugin
{
    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var \Magento\Company\Model\CompanyUserPermission
     */
    private $companyUserPermission;

    /**
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Company\Api\CompanyManagementInterface $companyManagement
     * @param \Magento\Company\Model\CompanyUserPermission $companyUserPermission
     */
    public function __construct(
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        \Magento\Company\Model\CompanyUserPermission $companyUserPermission
    ) {
        $this->userContext = $userContext;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->authorization = $authorization;
        $this->companyManagement = $companyManagement;
        $this->companyUserPermission = $companyUserPermission;
    }

    /**
     * View around execute plugin.
     *
     * @param NewAction $subject
     * @param \Closure $proceed
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        NewAction $subject,
        \Closure $proceed
    ) {
        $customerId = $this->userContext->getUserId();
        if ($customerId && $this->companyManagement->getByCustomerId($customerId)) {
            if (!$this->authorization->isAllowed('Magento_NegotiableQuote::manage')) {
                $resultRedirect = $this->resultRedirectFactory->create();

                if ($this->companyUserPermission->isCurrentUserCompanyUser()) {
                    $resultRedirect->setPath('company/accessdenied');
                } else {
                    $resultRedirect->setPath('noroute');
                }

                return $resultRedirect;
            }
        }

        return $proceed();
    }
}
