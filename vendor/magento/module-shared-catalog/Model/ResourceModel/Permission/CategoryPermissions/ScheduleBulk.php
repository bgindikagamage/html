<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions;

/**
 * Schedule bulk update of Categories Permissions.
 */
class ScheduleBulk
{
    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var \Magento\Framework\DataObject\IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var string
     */
    private $queueTopic = 'shared.catalog.category.permissions.updated';

    /**
     * @param \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory
     * @param \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     */
    public function __construct(
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operartionFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->groupRepository = $groupRepository;
    }
    
    /**
     * Create task with operations of update Category Permission from Shared Catalog permisison.
     *
     * @param array $categoryIds  Shared Category IDs array
     * @param array $groupIds  Shared Catalog customer group IDs
     * @param int $userId  Task creator User ID
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute(array $categoryIds, array $groupIds, $userId)
    {
        $operationCount = count($categoryIds);
        if ($operationCount > 0) {
            $bulkUuid = $this->identityService->generateId();
            $bulkDescription = __('Assign Categories to Shared Catalog');
            $operations = [];
            foreach ($categoryIds as $categoryId) {
                $dataToEncode = [
                    'category_id' => $categoryId,
                    'group_ids' => implode(',', $groupIds)
                ];
                $data = [
                    'data' => [
                        'bulk_uuid' => $bulkUuid,
                        'topic_name' => $this->queueTopic,
                        'serialized_data' => $this->serializer->serialize($dataToEncode),
                        'status' => \Magento\AsynchronousOperations\Api\Data\OperationInterface::STATUS_TYPE_OPEN,
                    ]
                ];
                
                /** @var \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation */
                $operation = $this->operationFactory->create($data);
                $operations[] = $operation;
            }
            $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription, $userId);
            if (!$result) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while scheduling operations.')
                );
            }
        }
    }
}
