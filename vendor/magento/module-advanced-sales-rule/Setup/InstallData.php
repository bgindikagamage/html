<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor
     */
    protected $indexerProcessor;

    /**
     * @param \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor
     */
    public function __construct(
        \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor
    ) {
        $this->indexerProcessor = $indexerProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->indexerProcessor->reindexAll();
    }
}
