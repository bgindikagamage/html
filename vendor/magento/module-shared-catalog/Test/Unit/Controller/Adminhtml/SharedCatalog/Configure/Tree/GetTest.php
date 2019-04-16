<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Tree;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Tree\Get;

/**
 * Test for controller Adminhtml\SharedCatalog\Configure\Tree\Get.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTest extends \Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\AbstractJsonActionAction
{
    /** Sample data value */
    const DATA = 'sample data value';

    /**
     * @var \Magento\SharedCatalog\Model\Configure\Category\Tree|\PHPUnit\Framework\MockObject_MockObject
     */
    private $categoryTree;

    /**
     * @var \Magento\SharedCatalog\Model\Configure\Category\Tree\Renderer|\PHPUnit\Framework\MockObject_MockObject
     */
    private $categoryTreeRenderer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\WizardFactory|\PHPUnit\Framework\MockObject_MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $request;

    /**
     * @var Get
     */
    private $get;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $mapForGetParamMethod = [
            [\Magento\SharedCatalog\Model\Form\Storage\UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY, null, '3w4634dfgser'],
            ['filters', null, []]
        ];
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturnMap($mapForGetParamMethod);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryTree = $this->getMockBuilder(\Magento\SharedCatalog\Model\Configure\Category\Tree::class)
            ->setMethods(['getCategoryRootNode'])
            ->disableOriginalConstructor()->getMock();
        $this->categoryTreeRenderer = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\Configure\Category\Tree\Renderer::class)
            ->setMethods(['render'])
            ->disableOriginalConstructor()->getMock();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\Form\Storage\WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();

        parent::setUp();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->get = $this->objectManagerHelper->getObject(
            \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Tree\Get::class,
            [
                '_request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'categoryTree' => $this->categoryTree,
                'treeRenderer' => $this->categoryTreeRenderer,
                'storeManager'=> $this->storeManager,
                'wizardStorageFactory' => $this->wizardStorageFactory
            ]
        );
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $data = ['data' => self::DATA];
        $storeId = 1;
        $storage = $this->getMockBuilder(\Magento\SharedCatalog\Model\Form\Storage\Wizard::class)
            ->disableOriginalConstructor()->getMock();
        $group = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->wizardStorageFactory->expects($this->exactly(1))->method('create')->willReturn($storage);
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')->willReturn($group);
        $group->expects($this->atLeastOnce())->method('getId')->willReturn($storeId);
        $storage->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $categoryRootNode = $this->getMockBuilder(\Magento\Framework\Data\Tree\Node::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->categoryTree->expects($this->exactly(1))->method('getCategoryRootNode')->willReturn($categoryRootNode);

        $this->categoryTreeRenderer->expects($this->once())
            ->method('render')
            ->with($categoryRootNode)
            ->will($this->returnValue(static::DATA));
        $this->createJsonResponse($data);
        $result = $this->get->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
    }
}
