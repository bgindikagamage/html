<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Plugin\Customer\Model\ResourceModel\Grid;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Model\ResourceModel\Grid\Collection;

/**
 * Plugin for customer grid collection.
 */
class CollectionPlugin
{
    /**
     * @var string
     */
    private $customerTypeExpressionPattern = '(IF(company_customer.company_id > 0, '
        . 'IF(company_customer.customer_id = company.super_user_id, "%d", "%d"), "%d"))';

    /**
     * @var array
     */
    private $expressionFields = [
        'customer_type'
    ];

    /**
     * Before loadWithFilter plugin.
     *
     * @param Collection $subject
     * @param bool $printQuery [optional]
     * @param bool $logQuery [optional]
     * @return array
     */
    public function beforeLoadWithFilter(
        Collection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        $subject->getSelect()->joinLeft(
            ['company_customer' => $subject->getTable('company_advanced_customer_entity')],
            'company_customer.customer_id = main_table.entity_id',
            ['company_customer.status']
        );
        $subject->getSelect()->joinLeft(
            ['company' => $subject->getTable('company')],
            'company.entity_id = company_customer.company_id',
            ['company.company_name']
        );
        $subject->getSelect()->columns([
            'customer_type' => new \Zend_Db_Expr($this->prepareCustomerTypeColumnExpression())
        ]);
        return [$printQuery, $logQuery];
    }

    /**
     * Around addFieldToFilter plugin.
     *
     * @param Collection $subject
     * @param \Closure $proceed
     * @param string|array $field
     * @param null|string|array $condition [optional]
     * @return Collection
     */
    public function aroundAddFieldToFilter(
        Collection $subject,
        \Closure $proceed,
        $field,
        $condition = null
    ) {
        $fieldMap = $this->getFilterFieldsMap();

        if (!isset($fieldMap['fields'][$field])) {
            return $proceed($field, $condition);
        }

        $fieldName = $fieldMap['fields'][$field];
        if (!in_array($field, $this->expressionFields)) {
            $fieldName = $subject->getConnection()->quoteIdentifier($fieldName);
        }

        $condition = $subject->getConnection()->prepareSqlCondition($fieldName, $condition);
        $subject->getSelect()->where($condition, null, \Magento\Framework\DB\Select::TYPE_CONDITION);

        return $subject;
    }

    /**
     * Get map for filterable fields.
     *
     * @return array
     */
    private function getFilterFieldsMap()
    {
        return [
            'fields' => [
                'email' => 'main_table.email',
                'customer_type' => $this->prepareCustomerTypeColumnExpression()
            ]
        ];
    }

    /**
     * Prepare expression for customer type column.
     *
     * @return string
     */
    private function prepareCustomerTypeColumnExpression()
    {
        return sprintf(
            $this->customerTypeExpressionPattern,
            CompanyCustomerInterface::TYPE_COMPANY_ADMIN,
            CompanyCustomerInterface::TYPE_COMPANY_USER,
            CompanyCustomerInterface::TYPE_INDIVIDUAL_USER
        );
    }
}
