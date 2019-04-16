<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Test\Unit\Plugin\Checkout\Helper;

/**
 * Class DataPluginTest.
 */
class DataPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Model\Customer\PermissionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $permission;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userContext;

    /**
     * @var \Magento\Company\Plugin\Checkout\Helper\DataPlugin
     */
    private $plugin;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->userContext = $this->createMock(
            \Magento\Authorization\Model\UserContextInterface::class
        );
        $this->customerRepository  = $this
            ->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->permission  = $this
            ->getMockBuilder(\Magento\Company\Model\Customer\PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCheckoutAllowed'])
            ->getMockForAbstractClass();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            \Magento\Company\Plugin\Checkout\Helper\DataPlugin::class,
            [
                'customerRepository' => $this->customerRepository,
                'userContext' => $this->userContext,
                'permission' => $this->permission
            ]
        );
    }

    /**
     * Test afterCanOnepageCheckout.
     *
     * @param bool $expectedResult
     * @dataProvider dataProviderAfterCanOnepageCheckout
     */
    public function testAfterCanOnepageCheckout($expectedResult)
    {
        $customer = $this->createMock(
            \Magento\Customer\Api\Data\CustomerInterface::class
        );
        $helper = $this->createMock(
            \Magento\Checkout\Helper\Data::class
        );
        $this->userContext->expects($this->any())->method('getUserId')->willReturn(1);
        $this->customerRepository->expects($this->once())->method('getById')->with(1)->willReturn($customer);
        $this->permission->expects($this->any())
            ->method('isCheckoutAllowed')
            ->with($customer)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->plugin->afterCanOnepageCheckout($helper, $expectedResult));
    }

    /**
     * Data provider afterCanOnepageCheckout.
     *
     * @return array
     */
    public function dataProviderAfterCanOnepageCheckout()
    {
        return [
            [false],
            [true]
        ];
    }
}
