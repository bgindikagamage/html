<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\ResourceModel\Company;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Company\Setup\InstallSchema;

/**
 * Resource collection for company entity. Used in entity repository for item list retrieving.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Standard collection initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Company\Model\Company::class, \Magento\Company\Model\ResourceModel\Company::class);
    }

    /**
     * Join advanced_customer_entity table.
     *
     * @return $this
     */
    public function joinAdvancedCustomerEntityTable()
    {
        $this->getSelect()->joinLeft(
            ['advanced_customer_entity' => $this->getTable(InstallSchema::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME)],
            'main_table.entity_id = advanced_customer_entity.company_id'
            . ' AND advanced_customer_entity.customer_id = main_table.super_user_id',
            [
                'job_title' => 'advanced_customer_entity.job_title'
            ]
        );

        return $this;
    }

    /**
     * Join directory_country_region table.
     *
     * @return $this
     */
    public function joinDirectoryCountryRegionTable()
    {
        $this->getSelect()->joinLeft(
            ['directory_country_region' => $this->getTable(InstallSchema::DIRECTORY_COUNTRY_REGION_TABLE_NAME)],
            'main_table.region_id = directory_country_region.region_id',
            [
                'region_name' => 'directory_country_region.default_name'
            ]
        );

        return $this;
    }

    /**
     * Join customer table.
     *
     * @return $this
     */
    public function joinCustomerTable()
    {
        $this->getSelect()->joinLeft(
            [
                'customer_grid_flat' => $this->getTable(InstallSchema::CUSTOMER_GRID_FLAT_TABLE_NAME),
                'advanced_customer_entity' => $this->getTable(InstallSchema::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME)
            ],
            'customer_grid_flat.entity_id = advanced_customer_entity.customer_id',
            [
                'company_admin' => 'customer_grid_flat.name',
                'gender' => 'customer_grid_flat.gender',
                'email_admin' => 'customer_grid_flat.email'
            ]
        );

        return $this;
    }
}
