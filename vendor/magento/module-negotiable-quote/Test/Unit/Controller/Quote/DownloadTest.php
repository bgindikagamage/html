<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

/**
 * Class DownloadTest
 */
class DownloadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $downloadProviderFactory;

    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\Download|\PHPUnit_Framework_MockObject_MockObject
     */
    private $download;

    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\DownloadProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $downloadProvider;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->downloadProvider = $this->createPartialMock(
            \Magento\NegotiableQuote\Model\Attachment\DownloadProvider::class,
            ['getAttachmentContents']
        );
        $this->downloadProviderFactory = $this->createPartialMock(
            \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory::class,
            ['create']
        );
        $this->logger = $this->getMockForAbstractClass(
            \Psr\Log\LoggerInterface::class,
            ['critical'],
            '',
            false,
            false,
            true,
            []
        );
        $this->downloadProviderFactory->expects($this->any())->method('create')->willReturn($this->downloadProvider);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $request = $this->createPartialMock(\Magento\Framework\App\Request\Http::class, ['getParam'], []);
        $request->expects($this->any())->method('getParam')->willReturn(1);
        $this->download = $objectManager->getObject(
            \Magento\NegotiableQuote\Controller\Quote\Download::class,
            [
                'request' => $request,
                'downloadProviderFactory' => $this->downloadProviderFactory,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test execute()
     */
    public function testExecute()
    {
        $this->downloadProvider->expects($this->once())->method('getAttachmentContents')->willReturn('data');
        $this->download->execute();
    }

    /**
     * Test execute()
     *
     * @expectedException \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteWithException()
    {
        $this->downloadProvider
            ->expects($this->once())
            ->method('getAttachmentContents')
            ->will(
                $this->throwException(new \Exception)
            );
        $this->logger->expects($this->once())->method('critical');
        $this->download->execute();
    }
}
