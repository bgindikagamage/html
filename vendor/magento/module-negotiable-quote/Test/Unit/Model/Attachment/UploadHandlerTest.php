<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Test\Unit\Model\Attachment;

/**
 * Unit test for UploadHandler class.
 */
class UploadHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystem;

    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\UploaderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\CommentAttachmentFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentFactory;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\UploadHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uploadHandler;

    /**
     * @var \Magento\NegotiableQuote\Model\Attachment\Uploader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uploader;

    /**
     * @var \Magento\NegotiableQuote\Model\CommentAttachment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attachment;

    /**
     * @var \Magento\NegotiableQuote\Api\Data\AttachmentContentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $file;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryWrite;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Read|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryRead;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->file = $this->getMockBuilder(\Magento\NegotiableQuote\Model\AttachmentContent::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBase64EncodedData', 'getName', 'getType'])
            ->getMock();
        $this->file->expects($this->atLeastOnce())
            ->method('getBase64EncodedData')
            ->willReturn(base64_encode('test-data'));
        $this->file->expects($this->atLeastOnce())->method('getName')->willReturn('file');
        $this->file->expects($this->atLeastOnce())->method('getType')->willReturn('text/plain');

        $this->directoryRead = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAbsolutePath'])
            ->getMock();
        $this->directoryRead->expects($this->atLeastOnce())->method('getAbsolutePath')->willReturn('some/path/to/');
        $this->directoryWrite = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['writeFile', 'getAbsolutePath'])
            ->getMock();
        $this->directoryWrite->expects($this->atLeastOnce())->method('getAbsolutePath')->willReturn('temp/path/to/');
        $this->filesystem = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryRead', 'getDirectoryWrite'])
            ->getMock();
        $this->filesystem->expects($this->atLeastOnce())
            ->method('getDirectoryRead')
            ->willReturn($this->directoryRead);
        $this->filesystem->expects($this->atLeastOnce())
            ->method('getDirectoryWrite')
            ->willReturn($this->directoryWrite);
        $this->uploader = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Attachment\Uploader::class)
            ->disableOriginalConstructor()
            ->setMethods(['setAllowRenameFiles', 'setFilesDispersion', 'save', 'processFileAttributes'])
            ->getMock();
        $this->uploader->expects($this->atLeastOnce())->method('processFileAttributes')->willReturn(true);
        $this->uploader->expects($this->atLeastOnce())->method('setAllowRenameFiles')->willReturnSelf();
        $this->uploader->expects($this->atLeastOnce())->method('setFilesDispersion')->willReturnSelf();
        $data = [
            'name' => 'file',
            'file' => 'file.txt',
            'type' => 'text/plain',
        ];
        $this->uploader->expects($this->any())->method('save')->willReturn($data);
        $this->uploaderFactory =
            $this->createPartialMock(\Magento\NegotiableQuote\Model\Attachment\UploaderFactory::class, ['create']);
        $this->uploaderFactory->expects($this->any())->method('create')->willReturn($this->uploader);
        $this->attachment = $this->createPartialMock(
            \Magento\NegotiableQuote\Model\CommentAttachment::class,
            ['setCommentId', 'setFileName', 'setFilePath', 'setFileType', 'getAttachmentId', 'save']
        );
        $this->attachment->expects($this->any())->method('setCommentId')->willReturnSelf();
        $this->attachment->expects($this->any())->method('setFileName')->willReturnSelf();
        $this->attachment->expects($this->any())->method('setFilePath')->willReturnSelf();
        $this->attachment->expects($this->any())->method('setFileType')->willReturnSelf();
        $this->attachment->expects($this->any())->method('getAttachmentId')->willReturn(1);
        $this->attachmentFactory =
            $this->createPartialMock(\Magento\NegotiableQuote\Model\CommentAttachmentFactory::class, ['create']);
        $this->attachmentFactory->expects($this->any())->method('create')->willReturn($this->attachment);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->uploadHandler = $objectManager->getObject(
            \Magento\NegotiableQuote\Model\Attachment\UploadHandler::class,
            [
                'filesystem' => $this->filesystem,
                'uploaderFactory' => $this->uploaderFactory,
                'attachmentFactory' => $this->attachmentFactory,
                'logger' => $this->logger,
                'commentId' => 1

            ]
        );
    }

    /**
     * Test process().
     *
     * @return void
     */
    public function testProcess()
    {
        $this->uploadHandler->process($this->file);
    }
}
