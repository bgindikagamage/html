<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\CollectionPlugin;

/**
 * Class CollectionPluginTest.
 */
class CollectionPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var \Magento\SharedCatalog\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var CollectionPlugin
     */
    private $collectionPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->customerSession->expects($this->any())->method('getCustomerGroupId')->willReturn(1);
        $this->config = $this->createPartialMock(\Magento\SharedCatalog\Model\Config::class, ['isActive']);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->collectionPlugin = $objectManager->getObject(
            \Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\CollectionPlugin::class,
            [
                'customerSession' => $this->customerSession,
                'storeManager' => $this->storeManager,
                'config' => $this->config
            ]
        );
    }

    /**
     * Test for beforeLoad().
     *
     * @return void
     */
    public function testBeforeLoad()
    {
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $subject = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $subject->expects($this->any())->method('isLoaded')->willReturn(false);
        $this->config->expects($this->once())->method('isActive')->willReturn(true);
        $subject->expects($this->once())->method('joinTable')->will($this->returnSelf());
        $result = $this->collectionPlugin->beforeLoad($subject);
        $this->assertEquals($result, [false, false]);
    }
}
