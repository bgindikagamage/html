<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\SaveHandler;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;

/**
 * Handler for shared catalog save. Save shared catalog and update its bound entities
 * (customer groups, category permissions, companies, etc.).
 */
class SharedCatalog
{
    /**
     * @var \Magento\SharedCatalog\Api\ProductItemManagementInterface
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogManagementInterface
     */
    private $sharedCatalogManagement;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogValidator
     */
    private $validator;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save
     */
    private $save;

    /**
     * @param \Magento\SharedCatalog\Api\ProductItemManagementInterface $sharedCatalogProductItemManagement
     * @param \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement
     * @param \Magento\SharedCatalog\Api\SharedCatalogManagementInterface $sharedCatalogManagement
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param \Magento\SharedCatalog\Model\SharedCatalogValidator $validator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save $save
     */
    public function __construct(
        \Magento\SharedCatalog\Api\ProductItemManagementInterface $sharedCatalogProductItemManagement,
        \Magento\SharedCatalog\Model\CustomerGroupManagement $customerGroupManagement,
        \Magento\SharedCatalog\Api\SharedCatalogManagementInterface $sharedCatalogManagement,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        \Magento\SharedCatalog\Model\SharedCatalogValidator $validator,
        \Psr\Log\LoggerInterface $logger,
        \Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save $save
    ) {
        $this->sharedCatalogProductItemManagement = $sharedCatalogProductItemManagement;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->sharedCatalogManagement = $sharedCatalogManagement;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->save = $save;
    }

    /**
     * Shared Catalog saving.
     *
     * If it is a new shared catalog then customer group will be created.
     * If it is an existing shared catalog and the shared catalog name changes then related customer group name updated
     * will be updated.
     * If a shared catalog type is being changed to public then all companies from the current public shared catalog
     * to the new public shared catalog will be reassigned.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @param SharedCatalogInterface $originalSharedCatalog
     * @return SharedCatalogInterface
     * @throws CouldNotSaveException
     * @throws \Exception
     */
    public function execute(SharedCatalogInterface $sharedCatalog, SharedCatalogInterface $originalSharedCatalog)
    {
        try {
            $this->validator->isDirectChangeToCustom($sharedCatalog);
            $isNotFirstPublicCatalog = $this->sharedCatalogManagement->isPublicCatalogExist()
                && ($sharedCatalog->getType() == SharedCatalogInterface::TYPE_PUBLIC);
            $this->save->prepare($sharedCatalog);
            $this->save->execute($sharedCatalog);
            $this->customerGroupManagement->updateCustomerGroup($sharedCatalog);
            if (!$originalSharedCatalog->getId()) {
                $this->catalogPermissionManagement
                    ->setDenyPermissionsForCustomerGroup($sharedCatalog->getCustomerGroupId());
            }
            if ($isNotFirstPublicCatalog) {
                $customerGroupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();
                $this->catalogPermissionManagement->reassignForRootCategories($customerGroupIds);
                $this->sharedCatalogProductItemManagement->addPricesForPublicCatalog();
            }
        } catch (CouldNotSaveException $e) {
            throw $e;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            throw new CouldNotSaveException(__('Could not save shared catalog.'));
        }
        return $sharedCatalog;
    }
}
