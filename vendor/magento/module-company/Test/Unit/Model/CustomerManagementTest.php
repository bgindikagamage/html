<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Unit test for Magento\Company\Model\CustomerRetriever class.
 */
class CustomerRetrieverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Model\CustomerRetriever
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getList'])
            ->getMockForAbstractClass();
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\Company\Model\CustomerRetriever::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * Test retrieveByEmail method.
     *
     * @param $customer
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $call
     * @param \PHPUnit_Framework_MockObject_Stub_Exception|\PHPUnit_Framework_MockObject_Stub_Return $result
     * @return void
     * @dataProvider retrieveCustomerDataProvider
     */
    public function testRetrieveByEmail($customer, $call, $result)
    {
        $email = 'customer@example.com';
        $this->customerRepository->expects($this->once())->method('get')->with($email)->will($result);
        $searchCriteria = $this
            ->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($call)->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($call)->method('setPageSize')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($call)->method('create')->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(\Magento\Framework\Api\SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($call)->method('getItems')->willReturn([$customer]);
        $this->customerRepository
            ->expects($call)
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $this->assertEquals($customer, $this->model->retrieveByEmail($email));
    }

    /**
     * Data provider for retrieveCustomer method.
     *
     * @return array
     */
    public function retrieveCustomerDataProvider()
    {
        $customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        return [
            [
                $customer,
                $this->atLeastOnce(),
                new \PHPUnit_Framework_MockObject_Stub_Exception(new NoSuchEntityException()),
            ],
            [
                $customer,
                $this->never(),
                new \PHPUnit_Framework_MockObject_Stub_Return($customer),
            ],
            [
                null,
                $this->never(),
                new \PHPUnit_Framework_MockObject_Stub_Return(null),
            ]
        ];
    }
}
