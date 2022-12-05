<?php

namespace Icecat\DataFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IcecatDatafeedQueue extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('icecat_datafeed_queue', 'job_id');
    }
}
