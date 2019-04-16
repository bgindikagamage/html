<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Block\Catalog\Product\View\Addto;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Block\Product\View;

/**
 * Requisition block
 */
class Requisition extends \Magento\Framework\View\Element\Template
{
    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var View
     */
    private $productView;

    /**
     * Constructor
     *
     * @param Context $context
     * @param HttpContext $httpContext
     * @param View $productView
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        View $productView,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
        $this->productView = $productView;
    }

    /**
     * Get Current Product.
     *
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->productView->getProduct();
    }

    /**
     * Get Current Product ID.
     *
     * @return string
     */
    public function getComponentId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        $isCustomerLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $isCustomerLoggedIn ? parent::_toHtml() : '';
    }
}
