<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;

/**
 * Test for SharedCatalogProductsLoader model.
 */
class SharedCatalogProductsLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $linkRepository;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogProductsLoader
     */
    private $sharedCatalogProductsLoader;

    /**
     * @var \Magento\Framework\Api\SearchCriteria|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteria;

    /**
     * @var \Magento\SharedCatalog\Api\Data\ProductItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogProduct;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->linkRepository = $this->getMockBuilder(ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()->getMock();
        $this->sharedCatalogProduct = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->sharedCatalogProductsLoader = $objectManager->getObject(
            \Magento\SharedCatalog\Model\SharedCatalogProductsLoader::class,
            [
                'linkRepository' => $this->linkRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    /**
     * Test for getAssignedProductsSkus().
     *
     * @return void
     */
    public function testGetAssignedProductsSkus()
    {
        $customerGroupId = 235;
        $sku = 'UJDU488865';
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(ProductItemInterface::CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($this->searchCriteria);
        $sharedCatalogProductSearchResults = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->linkRepository->expects($this->once())->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($sharedCatalogProductSearchResults);
        $sharedCatalogProductSearchResults->expects($this->once())->method('getItems')
            ->willReturn([$this->sharedCatalogProduct]);
        $this->sharedCatalogProduct->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);

        $this->assertEquals([$sku], $this->sharedCatalogProductsLoader->getAssignedProductsSkus($customerGroupId));
    }
}
