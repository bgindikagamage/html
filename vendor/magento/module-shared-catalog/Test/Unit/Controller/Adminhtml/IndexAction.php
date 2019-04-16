<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class IndexAction
 * @package Magento\SharedCatalog\Test\Unit\Controller\Adminhtml
 */
class IndexAction extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $objectManagerHelper;

    /** @var PageFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultPageFactory;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    protected function setUp()
    {
        $this->context =
            $this->createPartialMock(\Magento\Backend\App\Action\Context::class, ['getRequest', 'getSession']);
        $this->resultPageFactory = $this->createMock(\Magento\Framework\View\Result\PageFactory::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }
}
