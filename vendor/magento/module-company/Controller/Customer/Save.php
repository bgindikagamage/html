<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Customer;

use Magento\Framework\Exception\State\InputMismatchException;

/**
 * Update customer for company structure.
 */
class Save extends \Magento\Company\Controller\AbstractAction
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Model\Action\SaveCustomer
     */
    private $customerAction;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Company\Model\Action\SaveCustomer $customerAction
     * @param \Magento\Company\Model\Company\Structure $structureManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Model\Action\SaveCustomer $customerAction,
        \Magento\Company\Model\Company\Structure $structureManager
    ) {
        parent::__construct($context, $companyContext, $logger);
        $this->customerAction = $customerAction;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();

        $customerId = $request->getParam('customer_id');
        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());

        if (!in_array($customerId, $allowedIds['users'])) {
            throw new InputMismatchException(__('You are not allowed to do this.'));
        }

        try {
            $customer = $this->customerAction->update($request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonError(__('Something went wrong.'));
        }

        return $this->jsonSuccess($customer->__toArray(), __('The customer was successfully updated.'));
    }
}
