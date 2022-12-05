<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\Config\Source;

use Magento\Eav\Model\Config;
use Magento\Framework\Option\ArrayInterface;

class ProductAttributeDropDown implements ArrayInterface
{
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function toOptionArray()
    {
        $options[] = ['label' => __('Select Magento Product Attribute'), 'value' => ''];
        $allProductAttributes = array_keys($this->config->getEntityAttributes('catalog_product'));
        foreach ($allProductAttributes as $attributeCode) {
            $options[] = ['value' => $attributeCode, 'label' => __($this->config
                ->getAttribute('catalog_product', $attributeCode)
                ->getDefaultFrontendLabel())];
        }
        return $options;
    }
}
