<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\View
     */
    private $controller;

    /**
     * @var \Magento\Framework\View\Result\PageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourse;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManager;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRestriction;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\NegotiableQuote\Model\SettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $settingsProvider;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteHelper;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var RestrictionInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restrictionFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resourse = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getParam',
                'getFullActionName',
                'getRouteName',
                'isDispatched'
            ])
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addErrorMessage'])
            ->getMockForAbstractClass();
        $this->resultPageFactory = $this->getMockBuilder(\Magento\Framework\View\Result\PageFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourse->expects($this->any())->method('getParam')->with('quote_id')->will($this->returnValue(1));
        $this->customerRestriction = $this->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $redirectFactory = $this->getMockBuilder(\Magento\Framework\Controller\Result\RedirectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redirect = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redirect->expects($this->any())
            ->method('setPath')->will($this->returnSelf());
        $redirectFactory->expects($this->any())
            ->method('create')->will($this->returnValue($redirect));
        $this->negotiableQuoteManagement = $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->settingsProvider = $this->getMockBuilder(\Magento\NegotiableQuote\Model\SettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteHelper = $this->getMockBuilder(\Magento\NegotiableQuote\Helper\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restrictionFactory = $this->getMockBuilder(RestrictionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->controller = $objectManager->getObject(
            \Magento\NegotiableQuote\Controller\Quote\View::class,
            [
                '_request' => $this->resourse,
                'resultFactory' => $this->resultFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'resultRedirectFactory' => $redirectFactory,
                'customerRestriction' => $this->customerRestriction,
                'storeManager' => $this->storeManager,
                'messageManager' => $this->messageManager,
                'settingsProvider' => $this->settingsProvider,
                'quoteHelper' => $this->quoteHelper,
                '_response' => $this->response,
                'restrictionFactory' => $this->restrictionFactory
            ]
        );
    }

    /**
     * Test for isAllowed() method.
     *
     * @return void
     */
    public function testIsAllowed()
    {
        $this->prepareMocksForIsAllowed();

        $this->customerRestriction->expects($this->exactly(2))->method('isAllowed')
            ->withConsecutive(
                ['Magento_NegotiableQuote::view_quotes'],
                ['Magento_NegotiableQuote::view_quotes_sub']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
        $this->customerRestriction->expects($this->atLeastOnce())->method('isOwner')->willReturn(false);
        $quote = $this->prepareQuoteMock();
        $this->restrictionFactory->expects($this->atLeastOnce())->method('create')
            ->with($quote)
            ->willReturn($this->customerRestriction);
        $this->settingsProvider->expects($this->never())
            ->method('isCurrentUserCompanyUser');

        $this->assertInstanceOf(
            \Magento\Framework\App\ResponseInterface::class,
            $this->controller->dispatch($this->resourse)
        );
    }

    /**
     * Test for isAllowed() method when view quote does not exist.
     *
     * @return void
     */
    public function testIsAllowedIfQuoteNotExist()
    {
        $this->prepareMocksForIsAllowed();
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())->method('getNegotiableQuote')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());
        $this->restrictionFactory->expects($this->never())->method('create');
        $this->customerRestriction->expects($this->never())->method('isOwner');
        $this->settingsProvider->expects($this->never())->method('isCurrentUserCompanyUser');

        $this->assertInstanceOf(
            \Magento\Framework\App\ResponseInterface::class,
            $this->controller->dispatch($this->resourse)
        );
    }

    /**
     * Prepare mocks for isAllowed() test.
     *
     * @return void
     */
    private function prepareMocksForIsAllowed()
    {
        $this->settingsProvider->expects($this->once())->method('isModuleEnabled')->willReturn(true);
        $this->settingsProvider->expects($this->once())
            ->method('getCurrentUserType')
            ->willReturn(\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER);
        $this->quoteHelper->expects($this->once())->method('getCurrentUserId')->willReturn(1);
        $this->quoteHelper->expects($this->once())->method('isEnabled')->willReturn(true);
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $currentSroreIds = [1, 2, 3];
        $quoteStoreId = 1;

        $quote = $this->prepareQuoteMock();
        $quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn($quoteStoreId);
        $this->restrictionFactory->expects($this->atLeastOnce())->method('create')
            ->with($quote)
            ->willReturn($this->customerRestriction);
        $this->customerRestriction->expects($this->atLeastOnce())->method('isOwner')->willReturn(true);
        $websiteMock = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($currentSroreIds);

        $page = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'getLayout'])
            ->getMock();
        $title = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlock'])
            ->getMock();
        $block = $this->getMockBuilder(\Magento\Framework\View\Element\Html\Links::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActive'])
            ->getMock();
        $page->expects($this->atLeastOnce())->method('getConfig')->will($this->returnValue($config));
        $config->expects($this->atLeastOnce())->method('getTitle')->will($this->returnValue($title));
        $layout->expects($this->atLeastOnce())->method('getBlock')->will($this->returnValue($block));
        $page->expects($this->atLeastOnce())->method('getLayout')->will($this->returnValue($layout));
        $this->resultFactory
            ->expects($this->atLeastOnce())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE)
            ->willReturn($page);
        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\View\Result\Page::class, $result);
    }

    /**
     * Test for execute() method when negotiable quote view is forbidden.
     *
     * @return void
     */
    public function testExecuteWhenQuoteViewForbidden()
    {
        $currentSroreIds = [2, 3];
        $quoteStoreId = 1;

        $quote = $this->prepareQuoteMock();
        $quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn($quoteStoreId);
        $this->customerRestriction->expects($this->atLeastOnce())->method('isOwner')->willReturn(true);
        $this->restrictionFactory->expects($this->atLeastOnce())->method('create')
            ->with($quote)
            ->willReturn($this->customerRestriction);
        $websiteMock = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($currentSroreIds);
        $page = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'getLayout'])
            ->getMock();
        $this->resultPageFactory->expects($this->never())->method('create')->will($this->returnValue($page));
        $page->expects($this->never())->method('getConfig');
        $page->expects($this->never())->method('getLayout');
        $this->resultFactory
            ->expects($this->any())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE)
            ->willReturn($page);
        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Prepare Quote Mock.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareQuoteMock()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())->method('getNegotiableQuote')
            ->willReturn($quote);

        return $quote;
    }

    /**
     * Test for method execute with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $page = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock();
        $this->resultPageFactory->expects($this->any())->method('create')->will($this->returnValue($page));
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());
        $this->messageManager->expects($this->once())->method('addErrorMessage')
            ->with(__('Requested quote was not found'));

        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Test for method execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $errorMessage = __('test');

        $page = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock();
        $this->resultPageFactory->expects($this->any())->method('create')->will($this->returnValue($page));
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException($errorMessage));
        $this->messageManager->expects($this->once())->method('addErrorMessage')
            ->with($errorMessage);

        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
