<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Category;

use Magento\SharedCatalog\Model\Form\Storage\WizardFactory as WizardStorageFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;

/**
 * Assign category products to shared catalog.
 */
class Assign extends \Magento\SharedCatalog\Controller\Adminhtml\AbstractJsonAction
{
    /**
     * @var WizardStorageFactory
     */
    private $wizardStorageFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\SharedCatalog\Model\SharedCatalogAssignment
     */
    private $sharedCatalogAssignment;

    /**
     * Assign constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param WizardStorageFactory $wizardStorageFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        WizardStorageFactory $wizardStorageFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\SharedCatalog\Model\SharedCatalogAssignment $sharedCatalogAssignment
    ) {
        parent::__construct($context, $resultJsonFactory);
        $this->wizardStorageFactory = $wizardStorageFactory;
        $this->categoryRepository = $categoryRepository;
        $this->sharedCatalogAssignment = $sharedCatalogAssignment;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\SharedCatalog\Model\Form\Storage\Wizard $storage */
        $storage = $this->wizardStorageFactory->create([
            'key' => $this->getRequest()->getParam(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
        ]);

        $categoryId = (int)$this->getRequest()->getParam('category_id');
        $isAssign = (int)$this->getRequest()->getParam('is_assign');
        $isIncludeSubcategories = (int)$this->getRequest()->getParam('is_include_subcategories');

        $categoryIds = [];
        if ($isAssign || $isIncludeSubcategories) {
            $categoryIds = $this->categoryRepository->get($categoryId)->getAllChildren(true);
        }
        $categoryIds[] = $categoryId;

        if ($isAssign) {
            $assignProductSkus = $this->sharedCatalogAssignment->getAssignProductSkusByCategoryIds($categoryIds);
            $storage->assignProducts($assignProductSkus);
            $categoryIds = array_merge(
                $categoryIds,
                $this->sharedCatalogAssignment->getAssignCategoryIdsByProductSkus($assignProductSkus)
            );
            $storage->assignCategories($categoryIds);
        } else {
            $assignedCategoriesIds = $storage->getAssignedCategoriesIds();
            $assignedCategoriesIds = array_diff($assignedCategoriesIds, $categoryIds);
            $categoryProductSkus = $this->sharedCatalogAssignment->getProductSkusToUnassign(
                $categoryIds,
                $assignedCategoriesIds
            );
            $storage->unassignProducts($categoryProductSkus);
            $storage->unassignCategories($categoryIds);
        }

        return $this->createJsonResponse([
            'data' => [
                'status' => 1,
                'category' => $categoryId,
                'is_assign' => $isAssign
            ]
        ]);
    }
}
