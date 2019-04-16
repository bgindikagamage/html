<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Setup;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Add B2B Company: Access Denied page. Fill Company extension with B2C customers.
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var \Magento\Framework\DB\Query\Generator
     */
    private $queryGenerator;

    /**
     * @var int
     */
    private $batchSizeForCustomers = 10000;

    /**
     * InstallData constructor.
     *
     * @param PageFactory $pageFactory
     * @param \Magento\Framework\DB\Query\Generator $queryGenerator
     */
    public function __construct(
        PageFactory $pageFactory,
        \Magento\Framework\DB\Query\Generator $queryGenerator
    ) {
        $this->pageFactory = $pageFactory;
        $this->queryGenerator = $queryGenerator;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $pageData = [
            'title' => 'Company: Access Denied',
            'page_layout' => '2columns-right',
            'meta_keywords' => 'Page keywords',
            'meta_description' => 'Page description',
            'identifier' => 'access-denied-page',
            'content_heading' => 'Access Denied',
            'content' => 'You do not have permissions to view this page. '
                .'If you believe this is a mistake, please contact your company administrator.',
            'layout_update_xml' => '<referenceContainer name="root">'
                . '<referenceBlock name="breadcrumbs" remove="true"/>'
                . '</referenceContainer>',
            'is_active' => 1,
            'stores' => [0],
            'sort_order' => 0
        ];

        $setup->startSetup();
        $this->pageFactory->create()->setData($pageData)->save();
        $this->fillCustomers($setup);
        $setup->endSetup();
    }

    /**
     * Fill Company extension with B2C customers if it is installing over B2C Magento edition.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function fillCustomers(ModuleDataSetupInterface $setup)
    {
        $companyCustomerTableName = $setup->getTable(InstallSchema::ADVANCED_CUSTOMER_ENTITY_TABLE_NAME);
        $customerTableName = $setup->getTable('customer_entity');

        $select = $setup->getConnection()->select()
            ->from(
                ['customer' => $customerTableName],
                ['entity_id']
            )->joinLeft(
                ['company_customer' => $companyCustomerTableName],
                'customer.entity_id = company_customer.customer_id',
                []
            )->where(
                'company_customer.customer_id is NULL'
            );

        $iterator = $this->queryGenerator->generate('entity_id', $select, $this->batchSizeForCustomers);
        foreach ($iterator as $selectByRange) {
            $setup->getConnection()->query(
                $setup->getConnection()->insertFromSelect(
                    $selectByRange,
                    $companyCustomerTableName,
                    ['customer_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
                )
            );
        }
    }
}
