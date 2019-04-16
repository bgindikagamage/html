<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Page\Identifier;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\Page\DataProvider as CmsDataProvider;

/**
 * Class DataProvider
 */
class DataProvider extends CmsDataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Page $page */
        foreach ($items as $page) {
            $this->loadedData[$page->getId()] = [
                'page_id' => $page->getId(),
                'title' => $page->getTitle(),
            ];
        }

        return $this->loadedData;
    }
}
