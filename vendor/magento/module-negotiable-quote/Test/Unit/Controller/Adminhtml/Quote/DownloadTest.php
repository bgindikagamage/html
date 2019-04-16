<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

/**
 * Class DownloadTest
 */
class DownloadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $downloadProviderFactory;

    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\Download|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $download;

    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\DownloadProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $downloadProvider;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->downloadProvider = $this->createPartialMock(
            \Magento\NegotiableQuote\Model\Attachment\DownloadProvider::class,
            ['canDownload', 'getAttachmentContents']
        );
        $this->downloadProvider->expects($this->any())->method('getAttachmentContents')->willReturn('data');
        $this->downloadProviderFactory = $this->createPartialMock(
            \Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory::class,
            ['create']
        );
        $this->downloadProviderFactory->expects($this->any())->method('create')->willReturn($this->downloadProvider);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $request = $this->createPartialMock(\Magento\Framework\App\Request\Http::class, ['getParam'], []);
        $request->expects($this->any())->method('getParam')->with('attachmentId')->willReturn(1);
        $this->download = $objectManager->getObject(
            \Magento\NegotiableQuote\Controller\Quote\Download::class,
            [
                'request' => $request,
                'downloadProviderFactory' => $this->downloadProviderFactory
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
                $this->throwException(new \Magento\Framework\Exception\NotFoundException(__('Attachment not found.')))
            );
        $this->download->execute();
    }
}
