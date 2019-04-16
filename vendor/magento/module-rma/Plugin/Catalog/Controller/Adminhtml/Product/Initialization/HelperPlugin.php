<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Framework\App\RequestInterface;
use Magento\Rma\Model\Product\Source;

/**
 * Class HelperPlugin
 */
class HelperPlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Setting default values according to config settings
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitialize(Helper $subject, ProductInterface $product)
    {
        if (!empty($this->request->getParam('product')['use_config_is_returnable'])) {
            $product->setData('is_returnable', Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG);
        }

        return $product;
    }
}
