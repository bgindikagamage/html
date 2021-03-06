<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;

/**
 * Creates or updates a company admin customer entity with given data during company save process in admin panel.
 */
class CompanySuperUserGet
{
    /**
     * @var \Magento\Company\Model\Customer\CompanyAttributes
     */
    private $companyAttributes;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var \Magento\Company\Model\CustomerRetriever
     */
    private $customerRetriever;

    /**
     * @param \Magento\Company\Model\Customer\CompanyAttributes $companyAttributes
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Company\Model\CustomerRetriever $customerRetriever
     */
    public function __construct(
        \Magento\Company\Model\Customer\CompanyAttributes $companyAttributes,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Company\Model\CustomerRetriever $customerRetriever
    ) {
        $this->companyAttributes = $companyAttributes;
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->accountManagement = $accountManagement;
        $this->customerRetriever = $customerRetriever;
    }

    /**
     * Get company admin user or create one if it does not exist.
     *
     * @param array $data
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUserForCompanyAdmin(array $data)
    {
        unset($data['extension_attributes']);

        if (!isset($data['email'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('No company admin email is specified in request.')
            );
        }
        $companyAdminEmail = $data['email'];
        $customer = $this->customerRetriever->retrieveByEmail($companyAdminEmail);
        if (!$customer) {
            $customer = $this->customerDataFactory->create();
        }

        $this->dataObjectHelper->populateWithArray(
            $customer,
            $data,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );
        $companyAttributes = $this->companyAttributes->getCompanyAttributesByCustomer($customer);
        $customerStatus = $customer->getId() ?
            $companyAttributes->getStatus() : \Magento\Company\Api\Data\CompanyCustomerInterface::STATUS_ACTIVE;
        if (isset($data[CompanyCustomerInterface::JOB_TITLE])) {
            $companyAttributes->setJobTitle($data[CompanyCustomerInterface::JOB_TITLE]);
        }
        if (!$companyAttributes->getStatus()) {
            $companyAttributes->setStatus($customerStatus);
        }
        if ($customer->getId()) {
            $customer = $this->customerRepository->save($customer);
        } else {
            $customer = $this->accountManagement->createAccount($customer);
        }
        return $customer;
    }
}
