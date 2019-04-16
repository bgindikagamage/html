<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\ResourceModel;

/**
 * Resource model for Shared catalog Permission table.
 */
class Permission extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME,
            'permission_id'
        );
    }

    /**
     * Initialize unique scope for shared catalog permission.
     *
     * @return void
     */
    protected function _initUniqueFields()
    {
        parent::_initUniqueFields();
        $this->_uniqueFields[] = [
            'field' => ['category_id', 'website_id', 'customer_group_id'],
            'title' => __('Permission with the same scope'),
        ];
    }

    /**
     * Delete Shared Catalog category permissions connected with the category.
     *
     * @param int $categoryId
     * @return void
     */
    public function deleteItems($categoryId)
    {
        $tableName = $this->getTable(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME);
        $select = $this->getConnection()->select()
            ->from($tableName)
            ->where('category_id = ?', $categoryId);

        $this->getConnection()->query($this->getConnection()->deleteFromSelect($select, $tableName));
    }

    /**
     * Add shared catalog category permissions in bulk.
     *
     * @param array $data
     * @return int Number of affected rows
     */
    public function addPermissions(array $data)
    {
        return $this->getConnection()->insertOnDuplicate(
            $this->getTable(\Magento\SharedCatalog\Setup\InstallSchema::SHARED_CATALOG_PERMISSIONS_TABLE_NAME),
            $data
        );
    }
}
