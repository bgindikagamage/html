<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

/**
 * Resources pool.
 */
class ResourcePool
{
    /**
     * @var []
     */
    private $resources;

    /**
     * @param [] $resources
     */
    public function __construct(
        $resources = []
    ) {
        $this->resources = $resources;
    }

    /**
     * Get default resources.
     *
     * @return array
     */
    public function getDefaultResources()
    {
        return $this->resources;
    }
}
