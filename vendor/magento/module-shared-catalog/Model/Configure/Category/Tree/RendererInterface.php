<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Configure\Category\Tree;

/**
 * Interface RendererInterface
 */
interface RendererInterface
{
    /**
     * Render tree data
     *
     * @param \Magento\Framework\Data\Tree\Node $rootNode
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Tree\Node $rootNode);
}
