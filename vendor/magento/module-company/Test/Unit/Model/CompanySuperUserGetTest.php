<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit tests for CompanySuperUserGet model.
 */
class CompanySuperUserGetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\Company\Model\CompanySuperUserGet
     */
    private $companySuperUserGet;

    /**
     * @var \Magento\Company\Model\Customer\CompanyAttributes|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyAttributesMock;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerDataFactoryMock;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataObjectHelperMock;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accountManagementMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;

    /**
     * @var \Magento\Company\Api\Data\CompanyCustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyCustomerMock;

    /**
     * @var \Magento\Company\Model\CustomerRetriever|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRetrieverMock;

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp()
    {
        $this->companyAttributesMock = $this->getMockBuilder(\Magento\Company\Model\Customer\CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerDataFactoryMock = $this->getMockBuilder(
            \Magento\Customer\Api\Data\CustomerInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectHelperMock = $this->getMockBuilder(\Magento\Framework\Api\DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountManagementMock = $this->getMockBuilder(\Magento\Customer\Api\AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCustomerMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRetrieverMock = $this->getMockBuilder(\Magento\Company\Model\CustomerRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->companySuperUserGet = $this->objectManagerHelper->getObject(
            \Magento\Company\Model\CompanySuperUserGet::class,
            [
                'companyAttributes' => $this->companyAttributesMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customerDataFactory' => $this->customerDataFactoryMock,
                'dataObjectHelper' => $this->dataObjectHelperMock,
                'accountManagement' => $this->accountManagementMock,
                'customerRetriever' => $this->customerRetrieverMock
            ]
        );
    }

    /**
     * Test for getUserForCompanyAdmin method.
     *
     * @return void
     */
    public function testGetUserForCompanyAdmin()
    {
        $data = [
            'email' => 'companyadmin@test.com',
            \Magento\Company\Api\Data\CompanyCustomerInterface::JOB_TITLE => 'Job Title'
        ];
        $this->customerRetrieverMock
            ->expects($this->once())
            ->method('retrieveByEmail')
            ->with($data['email'])
            ->willReturn(null);
        $this->prepareMocksForGetUserForCompanyAdmin($data);
        $this->customerDataFactoryMock->expects($this->once())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->companyCustomerMock->expects($this->once())->method('getStatus')->willReturn(null);
        $this->companyCustomerMock->expects($this->once())->method('setStatus')
            ->with(\Magento\Company\Api\Data\CompanyCustomerInterface::STATUS_ACTIVE)
            ->willReturnSelf();
        $this->companyCustomerMock->expects($this->once())->method('setStatus')
            ->with(\Magento\Company\Api\Data\CompanyCustomerInterface::STATUS_ACTIVE)
            ->willReturnSelf();
        $this->accountManagementMock->expects($this->once())->method('createAccount')->with($this->customerMock)
            ->willReturn($this->customerMock);

        $this->assertEquals($this->customerMock, $this->companySuperUserGet->getUserForCompanyAdmin($data));
    }

    /**
     * Prepare mocks for testGetUserForCompanyAdmin test.
     *
     * @param array $data
     * @return void
     */
    private function prepareMocksForGetUserForCompanyAdmin($data)
    {
        $this->dataObjectHelperMock->expects($this->once())->method('populateWithArray')
            ->with(
                $this->customerMock,
                $data,
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
        $this->companyAttributesMock->expects($this->once())->method('getCompanyAttributesByCustomer')
            ->with($this->customerMock)
            ->willReturn($this->companyCustomerMock);
        $this->companyCustomerMock->expects($this->once())->method('setJobTitle')
            ->with($data[\Magento\Company\Api\Data\CompanyCustomerInterface::JOB_TITLE])
            ->willReturnSelf();
    }

    /**
     * Test for getUserForCompanyAdmin method when customer has ID.
     *
     * @return void
     */
    public function testGetUserForCompanyAdminCustomerHasId()
    {
        $data = [
            'email' => 'companyadmin@test.com',
            \Magento\Company\Api\Data\CompanyCustomerInterface::JOB_TITLE => 'Job Title'
        ];
        $this->customerRetrieverMock
            ->expects($this->once())
            ->method('retrieveByEmail')
            ->with($data['email'])
            ->willReturn($this->customerMock);
        $this->prepareMocksForGetUserForCompanyAdmin($data);
        $this->customerMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->companyCustomerMock->expects($this->atLeastOnce())->method('getStatus')->willReturn('dummy status');
        $this->customerRepositoryMock->expects($this->once())->method('save')->with($this->customerMock)
            ->willReturn($this->customerMock);

        $this->assertEquals($this->customerMock, $this->companySuperUserGet->getUserForCompanyAdmin($data));
    }

    /**
     * Test getUserForCompanyAdmin method when LocalizedException is thrown.
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage No company admin email is specified in request.
     * @return void
     */
    public function testGetUserForCompanyAdminWithLocalizedException()
    {
        $data = [];
        $this->companySuperUserGet->getUserForCompanyAdmin($data);
    }
}
