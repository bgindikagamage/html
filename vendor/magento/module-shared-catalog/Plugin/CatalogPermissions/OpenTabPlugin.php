<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\CatalogPermissions;

/**
 * Plugin for opening catalog permission tab on new category form.
 */
class OpenTabPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\SharedCatalog\Api\StatusInfoInterface
     */
    private $status;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\SharedCatalog\Api\StatusInfoInterface $status
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\SharedCatalog\Api\StatusInfoInterface $status
    ) {
        $this->request = $request;
        $this->status = $status;
    }

    /**
     * Open catalog permission tab on new category form.
     *
     * @param \Magento\Catalog\Model\Category\DataProvider $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareMeta(\Magento\Catalog\Model\Category\DataProvider $subject, array $result)
    {
        if (!$this->request->getParam('id') && $this->status->getActiveSharedCatalogStoreIds()) {
            $result['category_permissions']['arguments']['data']['config']['opened'] = true;
        }
        return $result;
    }
}
