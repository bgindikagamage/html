<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Plugin\Customer\Api;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin for negotiable quotes recalculation based on attached shipping addresses.
 */
class AddressRepositoryPlugin
{
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $context;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\Address
     */
    private $negotiableQuoteAddress;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\NegotiableQuote\Model\Customer\RecalculationStatus
     */
    private $recalculationStatus;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Model\Quote\Address $negotiableQuoteAddress
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagement
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\NegotiableQuote\Model\Customer\RecalculationStatus $recalculationStatus
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\NegotiableQuote\Model\Quote\Address $negotiableQuoteAddress,
        \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagement,
        \Psr\Log\LoggerInterface $logger,
        \Magento\NegotiableQuote\Model\Customer\RecalculationStatus $recalculationStatus
    ) {
        $this->context = $context;
        $this->negotiableQuoteAddress = $negotiableQuoteAddress;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->negotiableQuoteItemManagement = $negotiableQuoteItemManagement;
        $this->logger = $logger;
        $this->recalculationStatus = $recalculationStatus;
    }

    /**
     * Around save plugin that checks if negotiable quote with this shipping address should be recalculated.
     *
     * @param AddressRepositoryInterface $subject
     * @param \Closure $proceed
     * @param AddressInterface $address
     * @return AddressInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        AddressRepositoryInterface $subject,
        \Closure $proceed,
        AddressInterface $address
    ) {
        $quoteId = (int)$this->context->getRequest()->getParam('quoteId');
        if ($quoteId) {
            $needQuoteRecalculate = $this->recalculationStatus->isNeedRecalculate($address);
            $address = $proceed($address);
            $this->saveQuoteAddress($address, $quoteId);
            if ($needQuoteRecalculate) {
                $this->updateQuoteTaxes($address);
            }
        } else {
            $address = $proceed($address);
        }
        return $address;
    }

    /**
     * Save Negotiable quote address.
     *
     * @param AddressInterface $address
     * @param int $quoteId
     * @return void
     */
    private function saveQuoteAddress(AddressInterface $address, $quoteId)
    {
        try {
            $this->negotiableQuoteAddress->updateQuoteShippingAddress($quoteId, $address);
        } catch (NoSuchEntityException $e) {
            $this->context->getMessageManager()->addErrorMessage(__('Requested quote was not found'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->context->getMessageManager()->addErrorMessage(__('Unable to update shipping address'));
        }
    }

    /**
     * Update Quote taxes on address change.
     *
     * @param AddressInterface $address
     * @return void
     */
    private function updateQuoteTaxes(AddressInterface $address)
    {
        $list = $this->negotiableQuoteRepository->getListByCustomerId($address->getCustomerId());
        foreach ($list as $quote) {
            $negotiableQuote = $this->negotiableQuoteRepository->getById($quote->getId());
            if ($negotiableQuote->getStatus() != NegotiableQuoteInterface::STATUS_ORDERED
                && $negotiableQuote->getStatus() != NegotiableQuoteInterface::STATUS_CLOSED
            ) {
                $this->negotiableQuoteItemManagement
                    ->recalculateOriginalPriceTax($negotiableQuote->getId(), false, false, false);
            }
        }
    }
}
