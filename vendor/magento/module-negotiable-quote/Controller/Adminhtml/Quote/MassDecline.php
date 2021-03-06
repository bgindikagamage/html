<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class for mass declining negotiable quote.
 */
class MassDecline extends \Magento\NegotiableQuote\Controller\Adminhtml\Quote\AbstractMassAction
{
    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $restriction
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $restriction
    ) {
        parent::__construct(
            $context,
            $filter,
            $collectionFactory,
            $negotiableQuoteManagement
        );
        $this->quoteRepository = $quoteRepository;
        $this->restriction = $restriction;
    }

    /**
     * Decline quotes from collection.
     *
     * @param AbstractCollection $collection
     * @return ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function massAction(AbstractCollection $collection)
    {
        $message = $this->getRequest()->getParam('declineMessage');
        foreach ($collection as $quote) {
            $quote = $this->quoteRepository->get($quote->getId(), ['*']);
            $this->restriction->setQuote($quote);
            if ($this->restriction->canDecline()) {
                $this->negotiableQuoteManagement->decline($quote->getId(), $message);
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
