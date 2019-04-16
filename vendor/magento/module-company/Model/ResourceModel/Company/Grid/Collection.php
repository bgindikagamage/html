<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\ResourceModel\Company\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;

/**
 * Company grid collection. Provides data for companies grid.
 */
class Collection extends CompanyCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @var array
     */
    protected $mapper = [
        'entity_id' => 'main_table.entity_id',
        'job_title' => 'advanced_customer_entity.job_title',
        'company_admin' => 'customer_grid_flat.name',
        'email_admin' => 'customer_grid_flat.email',
        'country_id' => 'main_table.country_id',
        'status' => 'main_table.status',
        'customer_group_id' => 'main_table.customer_group_id'
    ];

    /**
     * Collection constructor
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $mainTable
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable = 'company',
        $resourceModel = \Magento\Company\Model\ResourceModel\Company::class,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @inheritdoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritdoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * Get search criteria
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinAdvancedCustomerEntityTable();
        $this->joinCustomerTable();
        $this->joinDirectoryCountryRegionTable();
        return $this;
    }

    /**
     * Add field filter to collection
     *
     * @param string|array $field
     * @param string|int|array|null $condition
     * @return \Magento\Cms\Model\ResourceModel\Block\Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'region') {
            $resultCondition = 'main_table.region like '
                . '\'' . $condition['like'] . '\' '
                . \Magento\Framework\DB\Select::SQL_OR
                . ' directory_country_region.default_name like '
                . '\'' . $condition['like'] . '\'';
            $this->getSelect()->where($resultCondition);
        } else {
            if (isset($this->mapper[$field])) {
                $field = $this->mapper[$field];
            }
            parent::addFieldToFilter($field, $condition);
        }
        return $this;
    }
}
