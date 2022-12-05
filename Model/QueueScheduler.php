<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class QueueScheduler extends AbstractModel implements IdentityInterface
{
    protected const CACHE_TAG = 'icecat_queue_scheduler';

    protected $_cacheTag = 'icecat_queue_scheduler';

    protected $_eventPrefix = 'icecat_queue_scheduler';

    protected function _construct()
    {
        $this->_init('Icecat\DataFeed\Model\ResourceModel\QueueScheduler');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
