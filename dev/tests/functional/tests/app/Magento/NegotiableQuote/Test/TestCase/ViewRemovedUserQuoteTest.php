<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\TestCase;

use Magento\Customer\Test\Fixture\Customer;
use Magento\Mtf\ObjectManager;
use Magento\Tax\Test\Fixture\TaxRule;
use Magento\CatalogRule\Controller\Adminhtml\Promo\Catalog;

/**
 * Test views contents of removed users in quotes.
 *
 * Preconditions:
 * 1. Register company admin.
 * 2. Register customer.
 * 3. Assign customer to a company.
 *
 * Test Flow:
 * 1. Request a quote as customer.
 * 2. Delete customer.
 * 3. Request a quote as company admin.
 * 4. Delete company.
 * 5. Perform assertions.
 *
 * @group NegotiableQuote
 * @ZephyrId MAGETWO-68396
 */
class ViewRemovedUserQuoteTest extends AbstractQuoteNegotiationTest
{
    /* tags */
    const MVP = 'yes';
    const TEST_TYPE = 'acceptance_test';
    /* end tags */

    /**
     * Company admin data.
     *
     * @var \Magento\Customer\Test\Fixture\Customer
     */
    private $companyAdmin;

    /**
     * Sub user data.
     *
     * @var \Magento\Customer\Test\Fixture\Customer
     */
    private $subUser;

    /**
     * Company admin quote.
     *
     * @var array
     */
    private $adminQuote;

    /**
     * Company.
     *
     * @var \Magento\Company\Test\Fixture\Company
     */
    private $company;

    /**
     * View contents of removed user.
     *
     * @param array $productsList
     * @param Customer $companyAdmin
     * @param Customer $userWithoutCompany
     * @param array $quote
     * @param string|null $configData
     * @return array
     */
    public function test(
        array $productsList,
        Customer $companyAdmin,
        Customer $userWithoutCompany,
        array $quote = [],
        $configData = null
    ) {
        $this->configData = $configData;
        $this->objectManager->create(
            \Magento\Config\Test\TestStep\SetupConfigurationStep::class,
            ['configData' => $this->configData]
        )->run();

        // Preconditions
        $companyAdmin->persist();
        $this->subUser = $userWithoutCompany;
        $this->subUser->persist();
        $this->companyAdmin = $companyAdmin;
        $this->company = $this->fixtureFactory->createByCode(
            'company',
            [
                'dataset' => 'company_with_required_fields_and_sales_rep',
                'data' => [
                    'email' => $this->companyAdmin->getEmail(),
                ],
            ]
        );
        $this->company->persist();
        $subUser = $this->fixtureFactory->createByCode(
            'customer',
            [
                'dataset' => 'company_customer_job_phone',
                'data' => [
                    'email' => $this->subUser->getEmail(),
                ],
            ]
        );
        $this->quote = $quote;
        $this->products = $this->createProducts($productsList);

        $this->performAdminActions($subUser);
        $this->performSubUserActions();
        $this->deleteCustomer();
        $this->deleteCompany();

        return [
            'adminQuote' => $this->adminQuote,
            'subUserQuote' => $this->quote,
            'admin' => $this->companyAdmin,
            'subUser' => $this->subUser,
            'company' => $this->company
        ];
    }

    /**
     * Perform company admin actions.
     *
     * @param \Magento\Mtf\Fixture\FixtureInterface $subUser
     *
     * @return void
     */
    private function performAdminActions(\Magento\Mtf\Fixture\FixtureInterface $subUser)
    {
        $this->loginCustomer($this->companyAdmin);
        $this->companyPage->open();
        $this->companyPage->getTreeControl()->clickAddCustomer();
        $this->companyPage->getCustomerPopup()->fill($subUser);
        $this->companyPage->getCustomerPopup()->setJobTitle($subUser->getJobTitle());
        $this->companyPage->getCustomerPopup()->setTelephone($subUser->getTelephone());
        $this->companyPage->getCustomerPopup()->submit();
        $this->addToCart($this->products);
        $quote = $this->quote;
        $quote['quote-name'] .= time();
        $this->requestQuote($quote);
        $this->adminQuote = $quote;
    }

    /**
     * Request a quote and place order.
     *
     * @return void
     */
    private function performSubUserActions()
    {
        $this->logoutCustomerOnFrontendStep->run();
        $this->loginCustomer($this->subUser);
        $quote = $this->quote;
        $quote['quote-name'] .= time();
        $this->addToCart($this->products);
        $this->requestQuote($quote);
        $this->quote = $quote;
    }

    /**
     * Delete customer on frontend.
     *
     * @return void
     */
    private function deleteCustomer()
    {
        $this->loginCustomer($this->companyAdmin);
        $this->companyPage->open();
        $this->companyPage->getTree()->selectFirstChild();
        $this->companyPage->getTreeControl()->clickDeleteSelected();
        $this->companyPage->getDeletePopup()->submit();
    }

    /**
     * Delete company.
     *
     * @return void
     */
    private function deleteCompany()
    {
        $this->objectManager->create(
            \Magento\Company\Test\TestStep\DeleteCompanyStep::class,
            ['companyName' => $this->company->getCompanyName()]
        )->run();
    }
}
