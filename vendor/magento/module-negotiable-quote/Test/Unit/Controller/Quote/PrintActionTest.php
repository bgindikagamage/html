<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

/**
 * Class PrintActionTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrintActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\PrintAction
     */
    private $controller;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteHelper;

    /**
     * @var \Magento\Framework\View\Result\PageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $resultFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resource;

    /**
     * @var \Magento\NegotiableQuote\Model\SettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $settingsProvider;

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
     * Set up
     */
    protected function setUp()
    {
        $this->resource = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->settingsProvider = $this->createMock(\Magento\NegotiableQuote\Model\SettingsProvider::class);
        $this->resultPageFactory =
            $this->createPartialMock(\Magento\Framework\View\Result\PageFactory::class, ['create']);
        $this->quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->customerRestriction =
            $this->createMock(\Magento\NegotiableQuote\Model\Restriction\RestrictionInterface::class);
        $page = $this->createPartialMock(
            \Magento\Framework\View\Result\Page::class,
            ['getConfig', 'getTitle', 'set', 'getLayout']
        );
        $page->expects($this->any())->method('getConfig')->willReturnSelf();
        $page->expects($this->any())->method('getTitle')->willReturnSelf();
        $page->expects($this->any())->method('set')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('create')->will($this->returnValue($page));
        $this->resultFactory = $this->createPartialMock(\Magento\Framework\Controller\ResultFactory::class, ['create']);
        $this->resultFactory
            ->expects($this->any())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE)
            ->willReturn($page);
        $title = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $config = $this->createPartialMock(\Magento\Framework\View\Page\Config::class, ['getTitle'], []);
        $layout = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['getBlock'], []);
        $block = $this->createPartialMock(\Magento\Framework\View\Element\Html\Links::class, ['setActive'], []);
        $config->expects($this->any())->method('getTitle')->will($this->returnValue($title));
        $page->expects($this->any())->method('getConfig')->will($this->returnValue($config));
        $layout->expects($this->any())->method('getBlock')->will($this->returnValue($block));
        $page->expects($this->any())->method('getLayout')->will($this->returnValue($layout));
        $redirectFactory =
            $this->createPartialMock(\Magento\Framework\Controller\Result\RedirectFactory::class, ['create']);
        $redirect = $this->createPartialMock(\Magento\Framework\Controller\Result\Redirect::class, ['setPath'], []);
        $redirect->expects($this->any())
            ->method('setPath')->will($this->returnSelf());
        $redirectFactory->expects($this->any())
            ->method('create')->will($this->returnValue($redirect));
        $this->negotiableQuoteManagement =
            $this->getMockBuilder(\Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface::class)
            ->setMethods(['prepareForOpen'])
            ->getMockForAbstractClass();
        $this->quoteHelper = $this->createMock(\Magento\NegotiableQuote\Helper\Quote::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $address = $this->createMock(\Magento\NegotiableQuote\Model\Quote\Address::class);
        $this->controller = $objectManager->getObject(
            \Magento\NegotiableQuote\Controller\Quote\PrintAction::class,
            [
                'request' => $this->resource,
                'resultRedirectFactory' => $redirectFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'quoteHelper' => $this->quoteHelper,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'customerRestriction' => $this->customerRestriction,
                'settingsProvider' => $this->settingsProvider,
                'negotiableQuoteAddress' => $address,
                'resultFactory' => $this->resultFactory
            ]
        );
    }

    /**
     * Prepare quote before testing.
     *
     * @param int $quoteId
     */
    private function prepareQuote($quoteId)
    {
        $this->resource->expects($this->any())
            ->method('getParam')
            ->with('quote_id')
            ->will($this->returnValue($quoteId));
        $quote = $this->createMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getExtensionAttributes',
                'collectTotals'
            ]
        );
        $this->quoteRepository->expects($this->any())->method('get')->will($this->returnValue($quote));
        $quoteNegotiation = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $extensionAttributes = $this->getMockForAbstractClass(
            \Magento\Quote\Api\Data\CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes
            ->expects($this->any())
            ->method('getNegotiableQuote')
            ->will($this->returnValue($quoteNegotiation));
        $quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->will($this->returnValue($extensionAttributes));
        $this->customerRestriction->expects($this->any())->method('canSubmit')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('prepareForOpen');
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param int $quoteId
     * @param string $expect
     */
    public function testExecute($quoteId, $expect)
    {
        $this->prepareQuote($quoteId);
        $result = $this->controller->execute();
        $this->assertInstanceOf($expect, $result);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [1, \Magento\Framework\View\Result\Page::class],
            [0, \Magento\Framework\Controller\Result\Redirect::class]
        ];
    }
}
