<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;

/**
 * Class MassDeclineCheck
 */
class MassDeclineCheck extends \Magento\NegotiableQuote\Controller\Adminhtml\Quote\AbstractMassAction
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface
     */
    protected $restriction;

    /**
     * MassDeclineCheck constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param JsonFactory $resultJsonFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $restriction
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        JsonFactory $resultJsonFactory,
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $restriction
    ) {
        parent::__construct(
            $context,
            $filter,
            $collectionFactory,
            $negotiableQuoteManagement
        );
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->restriction = $restriction;
    }

    /**
     * {@inheritdoc}
     */
    protected function massAction(AbstractCollection $collection)
    {
        $response = new \Magento\Framework\DataObject();
        $declineableQuoteIds = [];
        foreach ($collection as $quote) {
            $quote = $this->quoteRepository->get($quote->getId(), ['*']);
            $this->restriction->setQuote($quote);
            if ($this->restriction->canDecline()) {
                $declineableQuoteIds[] = $quote->getId();
            }
        }
        $response->setData('items', $declineableQuoteIds);
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
