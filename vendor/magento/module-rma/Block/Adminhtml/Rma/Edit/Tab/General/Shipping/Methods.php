<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping;

/**
 * Class Methods
 *
 * @api
 * @since 100.0.2
 */
class Methods extends \Magento\Framework\View\Element\Template
{
    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Json encoder interface
     *
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        $this->_taxData = $taxData;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        if ($this->_coreRegistry->registry('current_rma')) {
            $this->setShippingMethods($this->_coreRegistry->registry('current_rma')->getShippingMethods());
        }
    }

    /**
     * Get shipping price
     *
     * @param float $price
     * @return float
     */
    public function getShippingPrice($price)
    {
        return $this->priceCurrency->convert($this->_taxData->getShippingPrice($price), true, false);
    }

    /**
     * Get rma shipping data in json format
     *
     * @param array $method
     * @return string
     */
    public function jsonData($method)
    {
        $data = [];
        $data['CarrierTitle'] = $method->getCarrierTitle();
        $data['MethodTitle'] = $method->getMethodTitle();
        $data['Price'] = $this->getShippingPrice($method->getPrice());
        $data['PriceOriginal'] = $method->getPrice();
        $data['Code'] = $method->getCode();

        return $this->_jsonEncoder->encode($data);
    }
}
