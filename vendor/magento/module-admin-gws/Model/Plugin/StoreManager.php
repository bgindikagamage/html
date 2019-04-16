<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model\Plugin;

class StoreManager
{
    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $role;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     */
    public function __construct(\Magento\AdminGws\Model\Role $role)
    {
        $this->role = $role;
    }

    /**
     * Returns the list of websites which are valid for the current user role.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $subject
     * @param \Magento\Store\Model\Website[] $result
     * @return \Magento\Store\Model\Website[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetWebsites(\Magento\Store\Model\StoreManagerInterface $subject, array  $result)
    {
        if (!$this->role->getIsAll()) {
            $websites = [];
            /** @var \Magento\Store\Model\Website $website */
            foreach ($result as $key => $website) {
                if (in_array($website->getWebsiteId(), $this->role->getRelevantWebsiteIds())) {
                    $websites[$key] = $website;
                }
            }
            return $websites;
        }
        return $result;
    }
}
