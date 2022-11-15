<?php

namespace Icecat\DataFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QueueScheduler extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('icecat_queue_scheduler', 'id');
    }
}
