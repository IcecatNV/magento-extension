<?php
namespace Icecat\DataFeed\Block;

class ProductAttachment extends \Magento\Framework\View\Element\Template
{
    protected $_productAttachmentCollection;
    protected $registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Icecat\DataFeed\Model\ResourceModel\ProductAttachment\CollectionFactory  $productAttachmentCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->_productAttachmentCollection = $productAttachmentCollection;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
    }

    public function getProductAttachmentCollection()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $currentProduct = $this->getCurrentProduct();
        $productId = $currentProduct->getId();
        $collection  = $this->_productAttachmentCollection->create()
                       ->addFieldToSelect('*')
                       ->addFieldToFilter('product_id', $productId)
                       ->addFieldToFilter('store_id', $storeId);
        return $collection;
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
}
