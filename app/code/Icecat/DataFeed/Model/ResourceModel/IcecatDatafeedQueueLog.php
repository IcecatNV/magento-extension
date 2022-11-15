<?php

namespace Icecat\DataFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IcecatDatafeedQueueLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('icecat_datafeed_queue_log', 'id');
    }
}
