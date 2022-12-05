<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\ResourceModel\ProductReview;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'icecat_product_review_collection';
    protected $_eventObject = 'review_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Icecat\DataFeed\Model\ProductReview', 'Icecat\DataFeed\Model\ResourceModel\ProductReview');
    }

    public function addEntityFilter($entityId, $storeId)
    {
        $this->getSelect()->where('product_id = ?', $entityId);
        $this->getSelect()->where('store_id = ?', $storeId);
        return $this;
    }
}
