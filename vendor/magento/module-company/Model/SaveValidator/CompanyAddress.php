<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\SaveValidator;

/**
 * Checks if company address is valid.
 */
class CompanyAddress implements \Magento\Company\Model\SaveValidatorInterface
{
    /**
     * @var \Magento\Company\Api\Data\CompanyInterface
     */
    private $company;

    /**
     * @var \Magento\Framework\Exception\InputException
     */
    private $exception;

    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    private $countryInformationAcquirer;

    /**
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @param \Magento\Framework\Exception\InputException $exception
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(
        \Magento\Company\Api\Data\CompanyInterface $company,
        \Magento\Framework\Exception\InputException $exception,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
    ) {
        $this->company = $company;
        $this->exception = $exception;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!empty($this->company->getCountryId())) {
            try {
                $countryInformation = $this->countryInformationAcquirer->getCountryInfo($this->company->getCountryId());
                $regions = $countryInformation->getAvailableRegions();
                if ($regions) {
                    $needError = true;
                    foreach ($regions as $region) {
                        if ($this->company->getRegionId() == $region->getId()) {
                            $needError = false;
                            break;
                        }
                    }
                    if ($needError) {
                        $this->exception->addError(
                            __(
                                'Invalid value of "%value" provided for the %fieldName field.',
                                ['fieldName' => 'region_id', 'value' => $this->company->getRegionId()]
                            )
                        );
                    }
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->exception->addError(
                    __(
                        'Invalid value of "%value" provided for the %fieldName field.',
                        ['fieldName' => 'country_id', 'value' => $this->company->getCountryId()]
                    )
                );
            }
        }
    }
}
