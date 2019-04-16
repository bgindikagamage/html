<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Ui\DataProvider\Configure;

use Magento\SharedCatalog\Model\Form\Storage\WizardFactory as WizardStorageFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;

/**
 * Products grid in shared catalog wizard data provider.
 */
abstract class AbstractDataProvider extends \Magento\SharedCatalog\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\WizardFactory
     */
    private $wizardStorageFactory;

    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\CategoryTree
     */
    private $categoryTree;

    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\Wizard
     */
    private $storage;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\App\RequestInterface $request
     * @param WizardStorageFactory $wizardStorageFactory
     * @param \Magento\SharedCatalog\Model\ResourceModel\CategoryTree $categoryTree
     * @param array $meta [optional]
     * @param array $data [optional]
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\App\RequestInterface $request,
        WizardStorageFactory $wizardStorageFactory,
        \Magento\SharedCatalog\Model\ResourceModel\CategoryTree $categoryTree,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $request, $meta, $data);
        $this->wizardStorageFactory = $wizardStorageFactory;
        $this->categoryTree = $categoryTree;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        switch ($filter->getField()) {
            case 'websites':
                if ($filter->getValue() != 0) {
                    $this->getCollection()->addWebsiteFilter($filter->getValue());
                }
                break;
            case 'fulltext':
                $this->getCollection()->addAttributeToFilter(
                    [
                        ['attribute' => 'name', 'like' => "%{$filter->getValue()}%"],
                        ['attribute' => 'sku', 'like' => "%{$filter->getValue()}%"]
                    ]
                );
                break;
            case 'store_id':
                $storeId = $filter->getValue();
                $this->getCollection()->addStoreFilter($storeId);
                break;
            default:
                $this->getCollection()->addAttributeToFilter(
                    $filter->getField(),
                    [$filter->getConditionType() => $filter->getValue()]
                );
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConfig(array $configData)
    {
        $configData = parent::prepareConfig($configData);
        return $this->prepareUrl($configData, 'update_url');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareCollection()
    {
        $filters = $this->request->getParam('filters');
        $categoryId = !empty($filters['category_id']) ? $filters['category_id'] : '';
        $collection = $this->categoryTree->getCategoryProductsCollectionById($categoryId);
        if (empty($filters['store_id']) && empty($filters['websites'])) {
            $collection->setStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        }
        $collection->addWebsiteNamesToResult();

        return $collection;
    }

    /**
     * Get shared catalog pricing wizard storage.
     *
     * @return \Magento\SharedCatalog\Model\Form\Storage\Wizard
     */
    protected function getStorage()
    {
        if (!$this->storage) {
            $this->storage = $this->wizardStorageFactory->create(
                ['key' => $this->request->getParam(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)]
            );
        }

        return $this->storage;
    }
}
