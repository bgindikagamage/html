<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Block\Company\Profile;

/**
 * Class PaymentMethod.
 */
class PaymentMethod extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Store\Api\StoreResolverInterface
     */
    private $storeResolver;

    /**
     * @var \Magento\Payment\Api\PaymentMethodListInterface
     */
    private $paymentMethodList;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\CompanyPayment\Model\Payment\AvailabilityChecker
     */
    private $availabilityChecker;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var array
     */
    private $paymentMethods = [];

    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Store\Api\StoreResolverInterface $storeResolver
     * @param \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\CompanyPayment\Model\Payment\AvailabilityChecker $availabilityChecker
     * @param \Magento\Company\Api\CompanyManagementInterface $companyManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Api\StoreResolverInterface $storeResolver,
        \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\CompanyPayment\Model\Payment\AvailabilityChecker $availabilityChecker,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeResolver = $storeResolver;
        $this->paymentMethodList = $paymentMethodList;
        $this->authorization = $authorization;
        $this->userContext = $userContext;
        $this->availabilityChecker = $availabilityChecker;
        $this->companyManagement = $companyManagement;
    }

    /**
     * Get payment methods.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        if (!$this->paymentMethods) {
            $paymentMethods = $this->paymentMethodList->getActiveList($this->storeResolver->getCurrentStoreId());
            $company = $this->companyManagement->getByCustomerId($this->userContext->getUserId());

            foreach ($paymentMethods as $paymentMethod) {
                if ($this->availabilityChecker->isAvailableForCompany($paymentMethod->getCode(), $company)) {
                    $this->paymentMethods[] = $paymentMethod->getTitle();
                }
            }

            sort($this->paymentMethods);
        }

        return $this->paymentMethods;
    }

    /**
     * Company has enabled payment methods.
     *
     * @return bool
     */
    public function hasPaymentMethods()
    {
        return count($this->getPaymentMethods()) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if ($this->authorization->isAllowed('Magento_Company::payment_information')) {
            return parent::_toHtml();
        }
        return '';
    }
}
