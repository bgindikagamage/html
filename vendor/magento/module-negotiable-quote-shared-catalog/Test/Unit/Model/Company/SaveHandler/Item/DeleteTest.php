<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Model\Company\SaveHandler\Item;

use Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;

/**
 * Unit test for Magento\NegotiableQuoteSharedCatalog\Model\Company\SaveHandler\Item\Delete class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SharedCatalog\Api\ProductItemRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollectionFactory;

    /**
     * @var Delete|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemDeleter;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteManagement;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\Company\Api\CompanyHierarchyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyHierarchy;

    /**
     * @var \Magento\NegotiableQuoteSharedCatalog\Model\Company\SaveHandler\Item\Delete
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductItemRepository = $this->getMockBuilder(
            \Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productCollectionFactory = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->itemDeleter = $this->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagement = $this->getMockBuilder(
            \Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(\Magento\SharedCatalog\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyHierarchy = $this->getMockBuilder(\Magento\Company\Api\CompanyHierarchyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\NegotiableQuoteSharedCatalog\Model\Company\SaveHandler\Item\Delete::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository,
                'productCollectionFactory' => $this->productCollectionFactory,
                'itemDeleter' => $this->itemDeleter,
                'quoteManagement' => $this->quoteManagement,
                'config' => $this->config,
                'companyHierarchy' => $this->companyHierarchy,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $initialCompanyId = 6;
        $companyId = 7;
        $productSku = 'simple';
        $productId = 1;
        $storeIds = [4, 6];
        $company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityId'])
            ->getMockForAbstractClass();
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($companyId);
        $initialCompany->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($initialCompanyId);
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(
                [ProductItemInterface::CUSTOMER_GROUP_ID, $initialCompanyId, 'eq'],
                [ProductItemInterface::CUSTOMER_GROUP_ID, $companyId, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogProductItemRepository->expects($this->atLeastOnce())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturnOnConsecutiveCalls([$productItem], []);
        $productItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->productCollectionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($productCollection);
        $productCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->with('sku', ['in' => [$productSku]])
            ->willReturnSelf();
        $productCollection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $product->expects($this->atLeastOnce())->method('getEntityId')->willReturn($productId);
        $this->config->expects($this->once())->method('getActiveSharedCatalogStoreIds')->willReturn($storeIds);
        $hierarchy = $this->getMockBuilder(\Magento\Company\Api\Data\HierarchyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $hierarchy->expects($this->once())->method('getEntityType')
            ->willReturn(\Magento\Company\Api\Data\HierarchyInterface::TYPE_CUSTOMER);
        $hierarchy->expects($this->once())->method('getEntityId')->willReturn(1);
        $this->companyHierarchy->expects($this->once())->method('getCompanyHierarchy')->willReturn([$hierarchy]);
        $this->quoteManagement->expects($this->once())
            ->method('retrieveQuoteItemsForCustomers')
            ->with([1], [$productId], $storeIds)
            ->willReturn([$quoteItem]);
        $this->itemDeleter->expects($this->once())->method('deleteItems')->with([$quoteItem]);

        $this->model->execute($company, $initialCompany);
    }
}
