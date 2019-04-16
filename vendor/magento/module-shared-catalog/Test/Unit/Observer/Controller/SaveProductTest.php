<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Observer\Controller;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Observer\Controller\SaveProduct;

/**
 * Test for Observer Controller\SaveProduct.
 */
class SaveProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SharedCatalog\Api\ProductManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productSharedCatalogManagement;

    /**
     * @var \Magento\SharedCatalog\Observer\Controller\SaveProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    private $saveProductObserver;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var \Magento\SharedCatalog\Model\CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->productSharedCatalogManagement = $this->getMockBuilder(ProductManagementInterface::class)
            ->setMethods(['updateProductSharedCatalogs'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create', 'getList'])
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->sharedCatalogRepository = $this->getMockBuilder(
            \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getItems'])
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository->expects($this->atLeastOnce())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturnSelf();
        $this->catalogPermissionManagement = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\CatalogPermissionManagement::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setAllowPermissions'])
            ->getMock();
        $this->saveProductObserver = (new ObjectManager($this))->getObject(
            SaveProduct::class,
            [
                'productSharedCatalogManagement' => $this->productSharedCatalogManagement,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    /**
     * Test for execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $sku = 'sku1';
        $sharedCatalogIds = [1, 2];
        $categoryIds = [6, 8];

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getSku')->willReturn($sku);
        $product->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['shared_catalog', null, $sharedCatalogIds],
                ['tier_price', null, null]
            ]);
        $product->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls(1, 2);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$sharedCatalog, $sharedCatalog]);
        $observer = $this->prepareObserver($product);
        $this->productSharedCatalogManagement
            ->expects($this->once())
            ->method('updateProductSharedCatalogs')
            ->with($sku, $sharedCatalogIds);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setAllowPermissions')
            ->willReturn(true);

        $this->saveProductObserver->execute($observer);
    }

    /**
     * Test for execute if SharedCatalog is null.
     *
     * @return void
     */
    public function testExecuteIfSharedCatalogIsNull()
    {
        $sku = 'sku1';
        $categoryIds = [6, 8];

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getSku')->willReturn($sku);
        $product->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['shared_catalog', null, null],
                ['tier_price', null, null]
            ]);
        $product->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls(1, 2);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$sharedCatalog, $sharedCatalog]);
        $observer = $this->prepareObserver($product);
        $this->productSharedCatalogManagement
            ->expects($this->once())
            ->method('updateProductSharedCatalogs')
            ->with($sku, []);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setAllowPermissions')
            ->willReturn(true);
        $this->saveProductObserver->execute($observer);
    }

    /**
     * Test for execute with product tier price set not empty.
     *
     * @return void
     */
    public function testExecuteWithTierPrice()
    {
        $sku = 'sku1';
        $sharedCatalogIds = [1, 2];
        $tierPricesMock = [
            ['cust_group' => 1,],
            ['cust_group' => 2,]
        ];
        $categoryIds = [6, 8];
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getSku')->willReturn($sku);
        $product->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['shared_catalog', null, $sharedCatalogIds],
                ['tier_price', null, $tierPricesMock]
            ]);
        $product->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCustomerGroupId'])
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturnOnConsecutiveCalls(1, 2);
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls(1, 2);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$sharedCatalog, $sharedCatalog]);

        $observer = $this->prepareObserver($product);

        $this->productSharedCatalogManagement
            ->expects($this->once())
            ->method('updateProductSharedCatalogs')
            ->with($sku, $sharedCatalogIds);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setAllowPermissions')
            ->willReturn(true);
        $this->saveProductObserver->execute($observer);
    }

    /**
     * Prepare observer mock object for execute method.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Framework\Event\Observer
     */
    private function prepareObserver(\Magento\Catalog\Model\Product $product)
    {
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
        $event->expects($this->once())->method('getProduct')->willReturn($product);
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        return $observer;
    }
}
