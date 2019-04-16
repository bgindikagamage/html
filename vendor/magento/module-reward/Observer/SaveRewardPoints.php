<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerRegistry;

/**
 * Class SaveRewardPoints
 */
class SaveRewardPoints implements ObserverInterface
{
    /**
     * Customer converter
     *
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        CustomerRegistry $customerRegistry
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_rewardFactory = $rewardFactory;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Update reward points for customer, send notification
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_rewardData->isEnabled()) {
            return $this;
        }

        $request = $observer->getEvent()->getRequest();
        $data = $request->getPost('reward');
        if ($data && !empty($data['points_delta'])) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
            $customer = $observer->getEvent()->getCustomer();

            if (!isset($data['store_id'])) {
                if ($customer->getStoreId() == 0) {
                    $defaultStore = $this->_storeManager->getDefaultStoreView();
                    if (!$defaultStore) {
                        $allStores = $this->_storeManager->getStores();
                        if (isset($allStores[0])) {
                            $defaultStore = $allStores[0];
                        }
                    }
                    $data['store_id'] = $defaultStore ? $defaultStore->getStoreId() : null;
                } else {
                    $data['store_id'] = $customer->getStoreId();
                }
            }
            $customerModel = $this->customerRegistry->retrieve($customer->getId());
            /** @var $reward \Magento\Reward\Model\Reward */
            $reward = $this->_rewardFactory->create();
            $reward->setCustomer($customerModel)
                ->setWebsiteId($this->_storeManager->getStore($data['store_id'])->getWebsiteId())
                ->loadByCustomer();

            $reward->addData($data);
            $reward->setAction(\Magento\Reward\Model\Reward::REWARD_ACTION_ADMIN)
                ->setActionEntity($customerModel)
                ->updateRewardPoints();
        }

        return $this;
    }
}
