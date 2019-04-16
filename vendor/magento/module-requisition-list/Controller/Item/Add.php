<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Controller\Item;

use Magento\Framework\Controller\ResultFactory;

/**
 * Add product to the requisition list.
 */
class Add extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\RequisitionList\Model\Action\RequestValidator
     */
    private $requestValidator;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItem\SaveHandler
     */
    private $requisitionListItemSaveHandler;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    private $product;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItem\Locator
     */
    private $requisitionListItemLocator;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\RequisitionList\Model\Action\RequestValidator $requestValidator
     * @param \Magento\RequisitionList\Model\RequisitionListItem\SaveHandler $requisitionListItemSaveHandler
     * @param \Magento\RequisitionList\Model\RequisitionListProduct $requisitionListProduct
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\RequisitionList\Model\RequisitionListItem\Locator $requisitionListItemLocator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\RequisitionList\Model\Action\RequestValidator $requestValidator,
        \Magento\RequisitionList\Model\RequisitionListItem\SaveHandler $requisitionListItemSaveHandler,
        \Magento\RequisitionList\Model\RequisitionListProduct $requisitionListProduct,
        \Psr\Log\LoggerInterface $logger,
        \Magento\RequisitionList\Model\RequisitionListItem\Locator $requisitionListItemLocator
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->requisitionListItemSaveHandler = $requisitionListItemSaveHandler;
        $this->requisitionListProduct = $requisitionListProduct;
        $this->logger = $logger;
        $this->requisitionListItemLocator = $requisitionListItemLocator;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect = $this->preExecute($resultRedirect);
        if ($redirect) {
            return $redirect;
        }
        $itemId = (int)$this->getRequest()->getParam('item_id');
        $listId = $this->findRequisitionListByItemId($itemId);

        try {
            $options = [];
            $productData = $this->requisitionListProduct->prepareProductData(
                $this->getRequest()->getParam('product_data')
            );
            if (is_array($productData->getOptions())) {
                $options = $productData->getOptions();
            }

            $redirect = $this->checkConfiguration($resultRedirect, $options, $itemId, $listId);
            if ($redirect) {
                return $redirect;
            }

            $message = $this->requisitionListItemSaveHandler->saveItem($productData, $options, $itemId, $listId);
            $this->messageManager->addSuccess($message);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            if ($itemId) {
                $this->messageManager->addError(__('We can\'t update your requisition list right now.'));
            } else {
                $this->messageManager->addErrorMessage(
                    __('We can\'t add the item to the Requisition List right now: %1.', $e->getMessage())
                );
            }
            $this->logger->critical($e);
        }

        if (!$itemId) {
            return $resultRedirect->setRefererUrl();
        }

        return $resultRedirect->setPath(
            'requisition_list/requisition/view',
            ['requisition_id' => $listId]
        );
    }

    /**
     * Check is product configuration correct and requisition list id exists.
     *
     * @param \Magento\Framework\Controller\ResultInterface $resultRedirect
     * @param array $options
     * @param int $itemId
     * @param int $listId
     * @return \Magento\Framework\Controller\ResultInterface|null
     */
    private function checkConfiguration(
        \Magento\Framework\Controller\ResultInterface $resultRedirect,
        array $options,
        $itemId,
        $listId
    ) {
        if (!$listId) {
            $this->messageManager->addError(__('We can\'t specify a requisition list.'));
            $resultRedirect->setPath('requisition_list/requisition/index');
            return $resultRedirect;
        }

        if (!$itemId && empty($options)
            && $this->requisitionListProduct->isProductShouldBeConfigured($this->getProduct())) {
            $this->messageManager->addErrorMessage(__('You must choose options for your item.'));
            $resultRedirect->setUrl($this->getProductConfigureUrl());
            return $resultRedirect;
        }

        return null;
    }

    /**
     * Check is add to requisition list action allowed for the current user and product exists.
     *
     * @param \Magento\Framework\Controller\ResultInterface $resultRedirect
     * @return \Magento\Framework\Controller\ResultInterface|null
     */
    private function preExecute(\Magento\Framework\Controller\ResultInterface $resultRedirect)
    {
        $result = $this->requestValidator->getResult($this->getRequest());
        if ($result) {
            return $result;
        }

        if (!$this->getProduct()) {
            $this->messageManager->addError(__('We can\'t specify a product.'));
            $resultRedirect->setPath('requisition_list/requisition/index');
            return $resultRedirect;
        }
        return null;
    }

    /**
     * Get product specified by product data.
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|bool
     */
    private function getProduct()
    {
        if ($this->product === null) {
            $productData = $this->requisitionListProduct->prepareProductData(
                $this->getRequest()->getParam('product_data')
            );
            $this->product = $this->requisitionListProduct->getProduct($productData->getSku());
        }
        return $this->product;
    }

    /**
     * Prepare product configure url.
     *
     * @return string
     */
    private function getProductConfigureUrl()
    {
        return $this->getProduct()->getUrlModel()->getUrl(
            $this->getProduct(),
            ['_fragment' => 'requisition_configure']
        );
    }

    /**
     * Find requisition list by item id.
     *
     * @param int $itemId
     * @return int|null
     */
    private function findRequisitionListByItemId($itemId)
    {
        $listId = $this->getRequest()->getParam('list_id');
        if (!$listId && $itemId) {
            $item = $this->requisitionListItemLocator->getItem($itemId);
            $listId = $item->getRequisitionListId();
        }

        return $listId;
    }
}
