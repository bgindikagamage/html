<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Model;

/**
 * Unit test for Magento\SharedCatalog\Model\CategoryPermissionsInvalidator class.
 */
class CategoryPermissionsInvalidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator
     */
    private $model;

    /**
     * @var \Magento\Framework\App\CacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerRegistryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->cacheMock = $this->createMock(\Magento\Framework\App\CacheInterface::class);
        $this->indexerRegistryMock = $this->createMock(\Magento\Framework\Indexer\IndexerRegistry::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\CategoryPermissionsInvalidator::class,
            [
                'cache' => $this->cacheMock,
                'indexerRegistry' => $this->indexerRegistryMock
            ]
        );
    }

    /**
     * Test invalidate method.
     *
     * @return void
     */
    public function testInvalidate()
    {
        $this->cacheMock->expects($this->once())
            ->method('clean')
            ->with(
                [
                    \Magento\Catalog\Model\Category::CACHE_TAG,
                    \Magento\Framework\App\Cache\Type\Block::CACHE_TAG,
                    \Magento\Framework\App\Cache\Type\Layout::CACHE_TAG
                ]
            );

        $categoryIndexerMock = $this->createMock(\Magento\Framework\Indexer\IndexerInterface::class);
        $productIndexerMock = $this->createMock(\Magento\Framework\Indexer\IndexerInterface::class);
        $this->indexerRegistryMock->expects($this->at(0))
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($categoryIndexerMock);
        $this->indexerRegistryMock->expects($this->at(1))
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($productIndexerMock);

        $categoryIndexerMock->expects($this->once())->method('invalidate');
        $productIndexerMock->expects($this->once())->method('invalidate');

        $this->model->invalidate();
    }
}
