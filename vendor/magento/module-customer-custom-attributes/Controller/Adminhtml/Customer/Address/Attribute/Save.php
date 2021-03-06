<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Helper\Address as HelperAddress;
use Magento\CustomerCustomAttributes\Helper\Data as HelperData;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Registry;
use Magento\Store\Model\WebsiteFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperAddress
     */
    protected $helperAddress;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Config $eavConfig
     * @param AttributeFactory $attrFactory
     * @param SetFactory $attrSetFactory
     * @param WebsiteFactory $websiteFactory
     * @param HelperData $helperData
     * @param HelperAddress $helperAddress
     * @param FilterManager $filterManager
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Config $eavConfig,
        AttributeFactory $attrFactory,
        SetFactory $attrSetFactory,
        WebsiteFactory $websiteFactory,
        HelperData $helperData,
        HelperAddress $helperAddress,
        FilterManager $filterManager
    ) {
        $this->helperData = $helperData;
        $this->helperAddress = $helperAddress;
        $this->filterManager = $filterManager;
        parent::__construct(
            $context,
            $coreRegistry,
            $eavConfig,
            $attrFactory,
            $attrSetFactory,
            $websiteFactory
        );
    }

    /**
     * Save attribute action
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $data = $this->getRequest()->getPostValue();
        if ($this->getRequest()->isPost() && $data) {
            /* @var $attributeObject \Magento\Customer\Model\Attribute */
            $attributeObject = $this->_initAttribute();

            //filtering
            try {
                $data = $this->helperAddress->filterPostData($data);
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                if (isset($data['attribute_id'])) {
                    $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                } else {
                    $resultRedirect->setPath('adminhtml/*/new', ['_current' => true]);
                }
                return $resultRedirect;
            }

            $attributeId = $this->getRequest()->getParam('attribute_id');
            if ($attributeId) {
                $attributeObject->load($attributeId);
                if ($attributeObject->getEntityTypeId() != $this->_getEntityType()->getId()) {
                    $this->messageManager->addError(__('You cannot edit this attribute.'));
                    $this->_getSession()->addAttributeData($data);
                    $resultRedirect->setPath('adminhtml/*/');
                    return $resultRedirect;
                }

                $data['attribute_code'] = $attributeObject->getAttributeCode();
                $data['is_user_defined'] = $attributeObject->getIsUserDefined();
                $data['frontend_input'] = $attributeObject->getFrontendInput();
                $data['is_user_defined'] = $attributeObject->getIsUserDefined();
                $data['is_system'] = $attributeObject->getIsSystem();
                if (isset($data['used_in_forms']) && is_array($data['used_in_forms'])) {
                    $data['used_in_forms'][] = 'adminhtml_customer_address';
                } elseif (!isset($data['used_in_forms_disabled'])
                    && isset($data['used_in_forms'])
                    && $data['used_in_forms'] === ''
                ) {
                    $data['used_in_forms'] = [];
                }
            } else {
                $data['backend_model'] = $this->helperData->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );
                $data['source_model'] = $this->helperData->getAttributeSourceModelByInputType($data['frontend_input']);
                $data['backend_type'] = $this->helperData->getAttributeBackendTypeByInputType($data['frontend_input']);
                $data['is_user_defined'] = 1;
                $data['is_system'] = 0;

                // add set and group info
                $data['attribute_set_id'] = $this->_getEntityType()->getDefaultAttributeSetId();
                /** @var $attrSet \Magento\Eav\Model\Entity\Attribute\Set */
                $attrSet = $this->_attrSetFactory->create();
                $data['attribute_group_id'] = $attrSet->getDefaultGroupId($data['attribute_set_id']);
                $data['used_in_forms'][] = 'adminhtml_customer_address';
            }

            $defaultValueField = $this->helperData->getAttributeDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $scopeKeyPrefix = $this->getRequest()->getParam('website') ? 'scope_' : '';
                $defaultValue = $this->getRequest()->getParam($scopeKeyPrefix . $defaultValueField);
                $data[$scopeKeyPrefix . 'default_value'] = $defaultValue
                    ? $this->filterManager->stripTags($defaultValue) : null;
            }

            $data['entity_type_id'] = $this->_getEntityType()->getId();
            $data['validate_rules'] = $this->helperData->getAttributeValidateRules($data['frontend_input'], $data);

            $attributeObject->addData($data);

            /**
             * Check "Use Default Value" checkboxes values
             */
            $useDefaults = $this->getRequest()->getPost('use_default');
            if ($useDefaults) {
                foreach ($useDefaults as $key) {
                    $attributeObject->setData($key, null);
                }
            }

            try {
                $attributeObject->save();
                $this->messageManager->addSuccess(__('You saved the customer address attribute.'));
                $this->_getSession()->setAttributeData(false);
                if ($this->getRequest()->getParam('back', false)) {
                    $resultRedirect->setPath(
                        'adminhtml/*/edit',
                        ['attribute_id' => $attributeObject->getId(), '_current' => true]
                    );
                } else {
                    $resultRedirect->setPath('adminhtml/*/');
                }
                return $resultRedirect;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_getSession()->setAttributeData($data);
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('We can\'t save the customer address attribute right now.')
                );
                $this->_getSession()->setAttributeData($data);
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            }
        }
        $resultRedirect->setPath('adminhtml/*/');
        return $resultRedirect;
    }
}
