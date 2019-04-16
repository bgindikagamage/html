<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\ResourceModel;

/**
 * SharedCatalog page mysql resource.
 */
class SharedCatalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Main table primary key field name.
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var \Magento\SharedCatalog\Api\CompanyManagementInterface $companyManagement,
     */
    private $companyManagement;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_TABLE_NAME, 'entity_id');
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\SharedCatalog\Api\CompanyManagementInterface $companyManagement
     * @param \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement
     * @param string|null $connectionName [optional]
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\SharedCatalog\Api\CompanyManagementInterface $companyManagement,
        \Magento\SharedCatalog\Model\CatalogPermissionManagement $catalogPermissionManagement,
        $connectionName = null
    ) {
        $this->companyManagement = $companyManagement;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        parent::__construct($context, $connectionName);
    }

    /**
     * Perform actions before object delete.
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeDelete($object);
        $this->companyManagement->unassignAllCompanies($object->getId());
        $this->catalogPermissionManagement->removeAllPermissions($object->getId());
        return $this;
    }
}
