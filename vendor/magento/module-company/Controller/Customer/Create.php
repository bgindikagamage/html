<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Customer;

use Magento\Company\Model\Company\Structure;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Company\Model\Action\SaveCustomer as CustomerAction;

/**
 * Controller for creating a customer.
 */
class Create extends \Magento\Company\Controller\AbstractAction
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @var \Magento\Company\Model\Action\SaveCustomer
     */
    private $customerAction;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param Structure $structureManager
     * @param CustomerAction $customerAction
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        Structure $structureManager,
        CustomerAction $customerAction
    ) {
        parent::__construct($context, $companyContext, $logger);
        $this->structureManager = $structureManager;
        $this->customerAction = $customerAction;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();

        $targetId = $request->getParam('target_id');
        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());

        if ($targetId && !in_array($targetId, $allowedIds['structures'])) {
            return $this->jsonError(__('You are not allowed to do this.'));
        } elseif (!$targetId) {
            $structure = $this->structureManager
                ->getStructureByCustomerId($this->companyContext->getCustomerId());
            if ($structure === null) {
                return $this->jsonError(__('Cannot create the customer.'));
            }
        }

        try {
            $customer = $this->customerAction->create($this->getRequest());
        } catch (InputMismatchException $e) {
            return $this->jsonError(
                __(
                    'A user with this email address already exists in the system. '
                    . 'Enter a different email address to create this user.'
                ),
                [
                    'field' => 'email'
                ]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonError(__('Something went wrong.'));
        }

        return $this->jsonSuccess($customer->__toArray(), __('The customer was successfully created.'));
    }
}
