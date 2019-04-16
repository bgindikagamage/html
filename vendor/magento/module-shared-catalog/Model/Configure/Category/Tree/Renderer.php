<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Configure\Category\Tree;

use Magento\Framework\Data\Tree\Node;

/**
 * Render shared catalog category tree at shared catalog configuration page.
 */
class Renderer implements RendererInterface
{
    /**
     * Render shared catalog category tree.
     *
     * @param Node $rootNode
     * @return array
     */
    public function render(Node $rootNode)
    {
        return $this->getNodeDataAsArray($rootNode);
    }

    /**
     * Populate shared catalog category tree with required information for rendering.
     *
     * @param Node $node
     * @param int $level Category nesting level [optional]
     * @return array
     */
    protected function getNodeDataAsArray($node, $level = 0)
    {
        $item = [];

        $item = $this->prepareNodeData($node, $item);

        if ($node->hasChildren()) {
            $item['children'] = [];
            foreach ($node->getChildren() as $child) {
                $item['children'][] = $this->getNodeDataAsArray($child, $level + 1);
            }
        }

        return $item;
    }

    /**
     * Populate shared catalog category tree node with required information for rendering.
     *
     * @param Node $node
     * @param array $data
     * @return array
     */
    protected function prepareNodeData($node, $data)
    {
        $data['text'] = $node->getName();
        $data['a_attr'] = [
            'data-category-name' => $node->getName()
        ];

        $productCount = $node->getLevel() <= 1 ?
            (int)$node->getRootProductCount() : (int)$node->getProductCount();
        $productAssigned = $node->getLevel() <= 1 ?
            (int)$node->getRootSelectedCount() : (int)$node->getSelectedCount();

        $data['data'] = [
            'id'    => $node->getId(),
            'name'  => $node->getName(),
            'product_count' => $productCount,
            'product_assigned' => $productAssigned,
            'is_checked' => (int)$node->getIsChecked(),
            'is_active' => (int)$node->getIsActive()
        ];
        return $data;
    }
}
