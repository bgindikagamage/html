<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Test\Unit\Model;

/**
 * Class PaymentMethodStatusTest.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentMethodStatusTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Payment\Api\PaymentMethodListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodList;

    /**
     * @var \Magento\Store\Api\StoreResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeResolver;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userContext;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var \Magento\Payment\Model\Checks\SpecificationFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodSpecificationFactory;

    /**
     * @var \Magento\Payment\Model\Method\InstanceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodInstanceFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteFactory;

    /**
     * @var \Magento\CompanyCredit\Model\PaymentMethodStatus
     */
    private $paymentMethodStatus;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->paymentMethodList = $this->createMock(
            \Magento\Payment\Api\PaymentMethodListInterface::class
        );
        $this->storeResolver = $this->createMock(
            \Magento\Store\Api\StoreResolverInterface::class
        );
        $this->userContext = $this->createMock(
            \Magento\Authorization\Model\UserContextInterface::class
        );
        $this->quoteRepository = $this->createMock(
            \Magento\Quote\Api\CartRepositoryInterface::class
        );
        $this->methodSpecificationFactory = $this->createPartialMock(
            \Magento\Payment\Model\Checks\SpecificationFactory::class,
            ['create']
        );
        $this->paymentMethodInstanceFactory = $this->createPartialMock(
            \Magento\Payment\Model\Method\InstanceFactory::class,
            ['create']
        );
        $this->quoteFactory = $this->createPartialMock(
            \Magento\Quote\Model\QuoteFactory::class,
            ['create']
        );

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->paymentMethodStatus = $objectManager->getObject(
            \Magento\CompanyCredit\Model\PaymentMethodStatus::class,
            [
                'paymentMethodList' => $this->paymentMethodList,
                'storeResolver' => $this->storeResolver,
                'userContext' => $this->userContext,
                'quoteRepository' => $this->quoteRepository,
                'methodSpecificationFactory' => $this->methodSpecificationFactory,
                'paymentMethodInstanceFactory' => $this->paymentMethodInstanceFactory,
                'quoteFactory' => $this->quoteFactory,
            ]
        );
    }

    /**
     * Test for method isEnabled.
     *
     * @return void
     */
    public function testIsEnabled()
    {
        $storeId = 1;
        $userId = 2;
        $this->storeResolver->expects($this->once())->method('getCurrentStoreId')->willReturn($storeId);
        $paymentMethod = $this->createMock(\Magento\Payment\Api\Data\PaymentMethodInterface::class);
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')->with($storeId)->willReturn([$paymentMethod]);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('companycredit');
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteRepository->expects($this->once())
            ->method('getActiveForCustomer')->with($userId)->willReturn($quote);
        $methodInstance = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        $this->paymentMethodInstanceFactory->expects($this->once())
            ->method('create')->with($paymentMethod)->willReturn($methodInstance);
        $check = $this->createMock(\Magento\Payment\Model\Checks\Composite::class);
        $this->methodSpecificationFactory->expects($this->once())
            ->method('create')->with('company')->willReturn($check);
        $check->expects($this->once())->method('isApplicable')->with($methodInstance, $quote)->willReturn(true);
        $this->assertTrue($this->paymentMethodStatus->isEnabled());
    }

    /**
     * Test for method isEnabled with exception.
     *
     * @return void
     */
    public function testIsEnabledWithException()
    {
        $storeId = 1;
        $userId = 2;
        $this->storeResolver->expects($this->once())->method('getCurrentStoreId')->willReturn($storeId);
        $paymentMethod = $this->createMock(\Magento\Payment\Api\Data\PaymentMethodInterface::class);
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')->with($storeId)->willReturn([$paymentMethod]);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('companycredit');
        $this->userContext->expects($this->exactly(2))->method('getUserId')->willReturn($userId);
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['setCustomerId']);
        $this->quoteRepository->expects($this->once())->method('getActiveForCustomer')->with($userId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);
        $quote->expects($this->once())->method('setCustomerId')->with($userId)->willReturnSelf();
        $methodInstance = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        $this->paymentMethodInstanceFactory->expects($this->once())
            ->method('create')->with($paymentMethod)->willReturn($methodInstance);
        $check = $this->createMock(\Magento\Payment\Model\Checks\Composite::class);
        $this->methodSpecificationFactory->expects($this->once())
            ->method('create')->with('company')->willReturn($check);
        $check->expects($this->once())->method('isApplicable')->with($methodInstance, $quote)->willReturn(true);
        $this->assertTrue($this->paymentMethodStatus->isEnabled());
    }
}
