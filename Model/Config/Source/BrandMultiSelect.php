<?php

namespace Icecat\DataFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class BrandMultiSelect implements ArrayInterface
{
	const XML_PATH_ICECAT_CONFIG_BRAND = 'datafeed/product_brand_fetch_type/brand';

	public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		Repository $attributeRepository,
		CollectionFactory $collectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeRepository = $attributeRepository;
		$this->collectionFactory = $collectionFactory;
    }

	public function getBrandField()
    {
		return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_CONFIG_BRAND, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
    public function toOptionArray()
    {
    	$brandValue = $brandValueFinalOption = [];
    	$brandAttributeCode = $this->getBrandField();
    	if ($brandAttributeCode) {
			$collection = $this->collectionFactory->create()
		       	->addAttributeToSelect($brandAttributeCode);
		   	$attributeType = $this->attributeRepository->get($brandAttributeCode)->getFrontendInput();
		   	if ($attributeType == 'select') {
		   		$attributeOptions = $this->attributeRepository->get($brandAttributeCode)->getOptions();
		   		$i = 0;
		       	foreach ($attributeOptions as $key => $attributeOption) {
		       		if($attributeOption->getValue()!= ''){
			       		$brandValueOption = ['value' => $attributeOption->getValue(), 'label' => __($attributeOption->getLabel())];
			   			$brandValueFinalOption[$i] = $brandValueOption;	
			   			$i++;		       			
		       		}
		       	}
		   	} else {
				
				$brandValueArray = [];
				foreach ($collection as $key => $collections) {
					$brandValueArray[$collections[$brandAttributeCode]] = $collections[$brandAttributeCode];
				}
		      	$i = 0;
				foreach ($brandValueArray as $key => $brandValueArrays) {
					$brandValueOption = ['value' => $brandValueArrays, 'label' => __($brandValueArrays)];
					$brandValueFinalOption[$i] = $brandValueOption;
					$i++;
				}
		   	}
		   	return $brandValueFinalOption;
    	}
    }
}
