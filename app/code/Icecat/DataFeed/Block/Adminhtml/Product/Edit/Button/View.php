<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\Product\Edit\Button;

use Icecat\DataFeed\Helper\Data;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Catalog\Model\Product\Url;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Store\Model\StoreManagerInterface;

class View extends Generic
{
    /**
     * @var Url $productUrl
     */
    private $productUrl;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;
    private Data $data;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Url $productUrl
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Url $productUrl,
        StoreManagerInterface $storeManager,
        Data $data
    ) {
        parent::__construct($context, $registry);
        $this->storeManager = $storeManager;
        $this->productUrl = $productUrl;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $moduleEnabled = (bool)$this->data->getIsModuleEnabled();
        if ($moduleEnabled) {
            $deleteConfirmMsg = __("You are about to update this single product from Icecat. Do you wish to continue?");
            return [
                'label' => __('Import Data From Icecat'),
                'on_click' => 'import_single_prod_info("' . $this->getButtonUrl() . '")',
                'class' => 'view action-secondary',
                'sort_order' => 10
            ];
        }

        return [];
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentStoreId(): int
    {
        $currentStoreId = (int)$this->storeManager->getStore()->getId();
        if ($currentStoreId === \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $currentStoreId = (int)$this->storeManager->getDefaultStoreView()->getId();
        }

        return $currentStoreId;
    }

    private function getButtonUrl()
    {
        $product = $this->getProduct();
        return $this->getUrl('icecat/index/productdata', ['id' => $product->getId()]);
    }
}
