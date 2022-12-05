<?php

namespace Icecat\DataFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class Stores implements ArrayInterface
{
    private StoreRepositoryInterface $storeRepository;

    /**
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository
    ) {
        $this->storeRepository = $storeRepository;
    }
    public function toOptionArray()
    {
        $options = [];
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            if ($store->getCode() == 'admin') {
                continue;
            }
            $options[] = ['value' => $store->getId(), 'label' => $store->getName()];
        }
        return $options;
    }
}
