<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Model;

/**
 * Class NegotiableQuoteTaxRecalculateTest
 */
class NegotiableQuoteTaxRecalculateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilder;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteItemManagement;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteTaxRecalculate
     */
    private $model;

    /**
     * Set up
     */
    protected function setUp()
    {
        $quoteId = 1;
        $this->quote = $this->getMockForAbstractClass(\Magento\Quote\Api\Data\CartInterface::class);
        $this->quote->expects($this->any())->method('getId')->will($this->returnValue($quoteId));
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(\Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'getList'])
            ->getMockForAbstractClass();
        $this->filterBuilder = $this->createMock(\Magento\Framework\Api\FilterBuilder::class);
        $this->filterBuilder
            ->expects($this->any())
            ->method('setField')
            ->with('extension_attribute_negotiable_quote.status')
            ->willReturnSelf();
        $this->filterBuilder
            ->expects($this->any())
            ->method('setConditionType')
            ->with('nin')
            ->willReturnSelf();
        $this->filterBuilder
            ->expects($this->any())
            ->method('setValue')
            ->willReturnSelf();
        $filter = $this->createMock(\Magento\Framework\Api\Filter::class);
        $this->filterBuilder->expects($this->atLeastOnce())->method('create')->willReturn($filter);
        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $searchResults = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);
        $this->searchCriteriaBuilder = $this->createPartialMock(
            \Magento\Framework\Api\Search\SearchCriteriaBuilder::class,
            ['addFilter', 'addSortOrder', 'create']
        );
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with($filter)
            ->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addSortOrder')
            ->with('entity_id', 'DESC')
            ->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->negotiableQuoteRepository
            ->expects($this->any())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->quote]);
        $this->quoteItemManagement = $this->getMockForAbstractClass(
            \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface::class
        );
        $this->quoteItemManagement->expects($this->any())
            ->method('recalculateOriginalPriceTax')
            ->with($quoteId, false, false, false)
            ->willReturn(true);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\NegotiableQuote\Model\NegotiableQuoteTaxRecalculate::class,
            [
                'quoteItemManagement' => $this->quoteItemManagement,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    /**
     * test RecalculateTax method.
     */
    public function testRecalculateTax()
    {
        $this->model->recalculateTax(true);
    }
}
