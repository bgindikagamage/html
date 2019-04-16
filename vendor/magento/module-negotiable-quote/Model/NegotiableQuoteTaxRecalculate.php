<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model;

use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Class NegotiableQuoteTaxRecalculate
 */
class NegotiableQuoteTaxRecalculate
{
    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * @var bool
     */
    protected $needRecalculate = false;

    /**
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        NegotiableQuoteItemManagementInterface $quoteItemManagement,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->quoteItemManagement = $quoteItemManagement;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Set need recalculate
     *
     * @param bool $needRecalculate
     * @return $this
     */
    public function setNeedRecalculate($needRecalculate)
    {
        $this->needRecalculate = $needRecalculate;
        return $this;
    }

    /**
     * Recalculate tax on all negotiable quote
     *
     * @param bool $needRecalculate
     * @return void
     */
    public function recalculateTax($needRecalculate = false)
    {
        if ($this->needRecalculate || $needRecalculate) {
            $filter = $this->filterBuilder
                ->setField('extension_attribute_negotiable_quote.status')
                ->setConditionType('nin')
                ->setValue([NegotiableQuoteInterface::STATUS_ORDERED, NegotiableQuoteInterface::STATUS_CLOSED])
                ->create();
            $this->searchCriteriaBuilder->addSortOrder('entity_id', 'DESC');
            $this->searchCriteriaBuilder->addFilter($filter);
            $searchCriteria = $this->searchCriteriaBuilder->create();

            $quotes = $this->negotiableQuoteRepository->getList($searchCriteria)->getItems();
            foreach ($quotes as $quote) {
                $this->quoteItemManagement->recalculateOriginalPriceTax($quote->getId(), false, false, false);
            }
        }
    }
}
