<?php

namespace Icecat\DataFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class BrandsSelectionsDropdown implements ArrayInterface
{
    public function toOptionArray()
    {

        return [
            ['value' => 0, 'label' => __('All Brands')],
            ['value' => 1, 'label' => __('Select Specific Brands')],
        ];
    }
}
