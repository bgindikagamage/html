<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider as UpdateIdProvider;

/**
 * Class RemoveButton
 */
class RemoveButton implements ButtonProviderInterface
{
    /**
     * @var EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var UpdateIdProvider
     */
    protected $updateIdProvider;

    /**
     * @var string
     */
    protected $entityIdentifier;

    /**
     * @var string
     */
    protected $jsRemoveModal;

    /**
     * @var string
     */
    protected $jsRemoveLoader;

    /**
     * @param EntityProviderInterface $entityProvider
     * @param UpdateIdProvider $updateIdProvider
     * @param string $entityIdentifier
     * @param string $jsRemoveModal
     * @param string $jsRemoveLoader
     */
    public function __construct(
        EntityProviderInterface $entityProvider,
        UpdateIdProvider $updateIdProvider,
        $entityIdentifier,
        $jsRemoveModal,
        $jsRemoveLoader
    ) {
        $this->entityProvider = $entityProvider;
        $this->updateIdProvider = $updateIdProvider;
        $this->entityIdentifier = $entityIdentifier;
        $this->jsRemoveModal = $jsRemoveModal;
        $this->jsRemoveLoader = $jsRemoveLoader;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->updateIdProvider->getUpdateId()) {
            $data = [
                'label' => __('Remove from Update'),
                'class' => 'reset',
                'sort_order' => 30,
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'Magento_Ui/js/form/button-adapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->jsRemoveModal,
                                    'actionName' => 'openModal'
                                ],
                                [
                                    'targetName' => $this->jsRemoveLoader,
                                    'actionName' => 'render',
                                    'params' => [
                                        [
                                            $this->entityIdentifier => $this->entityProvider->getId(),
                                            'update_id' => $this->updateIdProvider->getUpdateId(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $data;
    }
}
