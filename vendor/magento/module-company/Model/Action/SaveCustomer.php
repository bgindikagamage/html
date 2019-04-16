<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Action;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Company\Api\CompanyRepositoryInterface;

/**
 * Create or update customer from request.
 */
class SaveCustomer
{
    /**
     * @var \Magento\Company\Model\Action\Customer\Populator
     */
    private $customerPopulator;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var \Magento\Company\Model\Action\Customer\Assign
     */
    private $roleAssigner;

    /**
     * @var \Magento\Company\Model\Action\Customer\Create
     */
    private $customerCreator;

    /**
     * @param Customer\Populator          $customerPopulator
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface  $companyRepository
     * @param Customer\Assign             $roleAssigner
     * @param Customer\Create              $customerCreator
     */
    public function __construct(
        Customer\Populator $customerPopulator,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        Customer\Assign $roleAssigner,
        \Magento\Company\Model\Action\Customer\Create $customerCreator
    ) {
        $this->customerPopulator = $customerPopulator;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->roleAssigner = $roleAssigner;
        $this->customerCreator = $customerCreator;
    }

    /**
     * Create customer from request.
     *
     * @param RequestInterface $request
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function create(RequestInterface $request)
    {
        try {
            $customer = $this->customerRepository->get($request->getParam('email'));
            if ($this->hasCustomerCompany($customer)) {
                throw new \Magento\Framework\Exception\State\InputMismatchException(
                    __('A customer with the same email already assigned to company.')
                );
            }
        } catch (NoSuchEntityException $e) {
            $customer = null;
        }

        $customer = $this->customerPopulator->populate($request->getParams(), $customer);
        $targetId = $request->getParam('target_id');
        $customer = $this->customerCreator->execute($customer, $targetId);
        $this->roleAssigner->assignCustomerRole($customer, $request->getParam('role'));

        return $customer;
    }

    /**
     * Update customer from request.
     *
     * @param RequestInterface $request
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws InputMismatchException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update(RequestInterface $request)
    {
        $customerId = $request->getParam('customer_id');

        $customer = $this->customerRepository->getById($customerId);
        $company = $this->companyRepository->get(
            $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );
        $customer = $this->customerPopulator->populate(
            $request->getParams(),
            $customer
        );
        $this->customerRepository->save($customer);
        if ($company->getSuperUserId() != $customerId) {
            $this->roleAssigner->assignCustomerRole($customer, $request->getParam('role'));
        }

        return $customer;
    }

    /**
     * Has customer company.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return bool
     */
    private function hasCustomerCompany(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        return $customer->getExtensionAttributes()
        && $customer->getExtensionAttributes()->getCompanyAttributes()
        && (int)$customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId() > 0;
    }
}
