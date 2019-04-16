<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;

/**
 * Consumer for shared catalog permissions queue to update category permissions accordingly.
 */
class Consumer
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionsManagement;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param EntityManager $entityManager
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionsManagement
     * @param \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        EntityManager $entityManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionsManagement,
        \Magento\SharedCatalog\Model\SharedCatalogInvalidation $sharedCatalogInvalidation
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->catalogPermissionsManagement = $catalogPermissionsManagement;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
    }

    /**
     * Processing batch operations for update category permissions from shared catalog.
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationListInterface $operationList
     * @return void
     * @throws \Magento\Framework\DB\Adapter\DuplicateException
     * @throws \LogicException
     * @throws \Exception
     */
    public function processOperations(\Magento\AsynchronousOperations\Api\Data\OperationListInterface $operationList)
    {
        $updatedCategories = [];
        foreach ($operationList->getItems() as $index => $operation) {
            $serializedData = $operation->getSerializedData();
            $unserializedData = $this->serializer->unserialize($serializedData);
            $categoryId = $unserializedData['category_id'];
            $groupIds = explode(',', $unserializedData['group_ids']);
            $this->catalogPermissionsManagement->updateCategoryPermissions($categoryId, $groupIds);
            $updatedCategories[] = $categoryId;
        }
        $this->sharedCatalogInvalidation->reindexCatalogPermissions($updatedCategories);
        foreach ($operationList->getItems() as $index => $operation) {
            // save operation data and status
            $operation->setStatus(OperationInterface::STATUS_TYPE_COMPLETE);
            $operation->setResultMessage(null);
        }
        $this->entityManager->save($operationList);
    }
}
