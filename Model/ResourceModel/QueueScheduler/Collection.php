<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\ResourceModel\QueueScheduler;

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
        $this->_init('Icecat\DataFeed\Model\QueueScheduler', 'Icecat\DataFeed\Model\ResourceModel\QueueScheduler');
    }

    public function addEntityFilter($entityId, $storeId)
    {
        $this->getSelect()->where('id = ?', $entityId);
        //$this->getSelect()->where('store_id = ?', $storeId);
        return $this;
    }
}
