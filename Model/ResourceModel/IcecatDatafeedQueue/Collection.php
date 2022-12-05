<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\ResourceModel\IcecatDatafeedQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'job_id';
    protected $_eventPrefix = 'icecat_datafeed_queue_collection';
    protected $_eventObject = 'log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Icecat\DataFeed\Model\IcecatDatafeedQueue', 'Icecat\DataFeed\Model\ResourceModel\IcecatDatafeedQueue');
    }
}
