<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

/**
 * Management Customer Group for SharedCatalog.
 */
class CustomerGroupManagement
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @var bool|null
     */
    private $masterCatalog;

    /**
     * @var \Magento\Customer\Api\CustomerGroupConfigInterface
     */
    private $customerGroupConfig;

    /**
     * @var \Magento\Customer\Api\Data\GroupInterfaceFactory
     */
    private $groupFactory;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Customer\Api\CustomerGroupConfigInterface $customerGroupConfig
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $groupFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Customer\Api\CustomerGroupConfigInterface $customerGroupConfig,
        \Magento\Customer\Api\Data\GroupInterfaceFactory $groupFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
    ) {
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->customerGroupConfig = $customerGroupConfig;
        $this->groupFactory = $groupFactory;
        $this->groupRepository = $groupRepository;
    }

    /**
     * Check if master catalog should be displayed for customer group.
     *
     * @param int $customerGroupId
     * @return bool
     */
    public function isMasterCatalogAvailable($customerGroupId)
    {
        if ($this->masterCatalog === null) {
            $this->masterCatalog = in_array($customerGroupId, $this->getGroupIdsNotInSharedCatalogs());
        }
        return $this->masterCatalog;
    }

    /**
     * Get customer groups that are linked to shared catalog including guest customer group.
     *
     * @return array
     */
    public function getSharedCatalogGroupIds()
    {
        $collection = $this->groupCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['shared_catalog' => $collection->getTable('shared_catalog')],
            'main_table.customer_group_id = shared_catalog.customer_group_id',
            ['shared_catalog_id' => 'shared_catalog.entity_id']
        );
        $collection->getSelect()->where(
            '(shared_catalog.entity_id IS NOT NULL OR main_table.customer_group_id = ?)',
            \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID
        );
        return $collection->getColumnValues('customer_group_id');
    }

    /**
     * Get customer groups that are not linked to any shared catalog.
     *
     * @return array
     */
    public function getGroupIdsNotInSharedCatalogs()
    {
        $collection = $this->groupCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['shared_catalog' => $collection->getTable('shared_catalog')],
            'main_table.customer_group_id = shared_catalog.customer_group_id',
            ['shared_catalog_id' => 'shared_catalog.entity_id']
        );
        $collection->getSelect()->where(
            '(shared_catalog.entity_id IS NULL AND main_table.customer_group_id != ?)',
            \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID
        );
        return $collection->getColumnValues('customer_group_id');
    }

    /**
     * Set default customer group.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function setDefaultCustomerGroup(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog)
    {
        try {
            $this->customerGroupConfig->setDefaultCustomerGroup($sharedCatalog->getCustomerGroupId());
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not set default customer group'));
        }
    }

    /**
     * Create customer group for SharedCatalog.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @return \Magento\Customer\Api\Data\GroupInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createCustomerGroupForSharedCatalog(
        \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
    ) {
        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $customerGroup = $this->groupFactory->create();
        $customerGroup->setCode($sharedCatalog->getName());
        if ($sharedCatalog->getTaxClassId()) {
            $customerGroup->setTaxClassId($sharedCatalog->getTaxClassId());
        }
        try {
            $customerGroup = $this->groupRepository->save($customerGroup);
        } catch (\Magento\Framework\Exception\State\InvalidTransitionException $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__(
                'A customer group with this name already exists. Enter a different name to create a shared catalog.'
            ));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Could not save customer group.'));
        }

        return $customerGroup;
    }

    /**
     * Delete customer group by ID.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException If customer group cannot be deleted
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteCustomerGroupById(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog)
    {
        return $this->groupRepository->deleteById($sharedCatalog->getCustomerGroupId());
    }

    /**
     * Update customer group code and tax class id.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer group ID is not found
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateCustomerGroup(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog)
    {
        $customerGroup = $this->groupRepository->getById($sharedCatalog->getCustomerGroupId());
        $changeCustomerGroupTaxClassIdResult = $this->changeTaxClassId($customerGroup, $sharedCatalog->getTaxClassId());
        $changeCustomerGroupCodeResult = $this->changeCustomerGroupCode($customerGroup, $sharedCatalog->getName());

        if ($changeCustomerGroupTaxClassIdResult || $changeCustomerGroupCodeResult) {
            try {
                $this->groupRepository->save($customerGroup);
                return true;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    'Could not update shared catalog customer group'
                ));
            }
        }

        return false;
    }

    /**
     * Set customer group tax class id if new tax class id differs from the initial one.
     *
     * @param \Magento\Customer\Api\Data\GroupInterface $customerGroup
     * @param int $taxClassId
     * @return bool
     */
    private function changeTaxClassId(\Magento\Customer\Api\Data\GroupInterface $customerGroup, $taxClassId)
    {
        if ($customerGroup && $customerGroup->getTaxClassId() != $taxClassId) {
            $customerGroup->setTaxClassId($taxClassId);
            return true;
        }

        return false;
    }

    /**
     * Set customer group code if new code differs from the initial one and customer group is Not Logged In.
     *
     * @param \Magento\Customer\Api\Data\GroupInterface $customerGroup
     * @param string $customerGroupCode
     * @return bool
     */
    private function changeCustomerGroupCode(
        \Magento\Customer\Api\Data\GroupInterface $customerGroup,
        $customerGroupCode
    ) {
        if ($customerGroup && $customerGroup->getId() != \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID
            && $customerGroup->getCode() != $customerGroupCode
        ) {
            $customerGroup->setCode($customerGroupCode);
            return true;
        }

        return false;
    }
}
