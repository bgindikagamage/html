<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

/**
 * Handle customer management for company.
 */
class CustomerRetriever
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Retrieve customer from default website, if it is not there try to load from all websites.
     *
     * @param string $email
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveByEmail($email)
    {
        $customer = null;
        try {
            $customer = $this->customerRepository->get($email);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(\Magento\Customer\Api\Data\CustomerInterface::EMAIL, $email)
                ->setPageSize(1)
                ->create();
            $items = $this->customerRepository->getList($searchCriteria)->getItems();
            $customer = array_shift($items);
        }

        return $customer;
    }
}
