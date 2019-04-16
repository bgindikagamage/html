<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Mview\View\Attribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class Subscription implements statement building for staged entity attribute subscription
 *
 * @package Magento\CatalogStaging\Model\Mview\View\Attribute
 */
class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Magento\Framework\Mview\View\CollectionInterface $viewCollection
     * @param \Magento\Framework\Mview\ViewInterface $view
     * @param string $tableName
     * @param string $columnName
     * @param MetadataPool $metadataPool
     * @param string|null $entityInterface
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Framework\Mview\View\CollectionInterface $viewCollection,
        \Magento\Framework\Mview\ViewInterface $view,
        $tableName,
        $columnName,
        MetadataPool $metadataPool,
        $entityInterface = null
    ) {
        parent::__construct($resource, $triggerFactory, $viewCollection, $view, $tableName, $columnName);
        $this->entityMetadata = $metadataPool->getMetadata($entityInterface);
    }

    /**
     * Build trigger statement for INSERT, UPDATE, DELETE events
     *
     * @param string $event
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     * @return string
     */
    protected function buildStatement($event, $changelog)
    {
        $triggerBody = null;
        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                $triggerBody = "INSERT IGNORE INTO %1\$s (%2\$s) SELECT %3\$s FROM %4\$s WHERE %5\$s = NEW.%5\$s;";
                break;
            case Trigger::EVENT_DELETE:
                $triggerBody = "INSERT IGNORE INTO %1\$s (%2\$s) SELECT %3\$s FROM %4\$s WHERE %5\$s = OLD.%5\$s;";
                break;
            default:
                break;
        }
        $params = [
            $this->connection->quoteIdentifier(
                $this->resource->getTableName($changelog->getName())
            ),
            $this->connection->quoteIdentifier(
                $changelog->getColumnName()
            ),
            $this->connection->quoteIdentifier(
                $this->entityMetadata->getIdentifierField()
            ),
            $this->connection->quoteIdentifier(
                $this->resource->getTableName($this->entityMetadata->getEntityTable())
            ),
            $this->connection->quoteIdentifier(
                $this->entityMetadata->getLinkField()
            )
        ];
        return vsprintf($triggerBody, $params);
    }
}
