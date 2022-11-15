<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class IcecatDatafeedQueue extends AbstractModel implements IdentityInterface
{
    protected const CACHE_TAG = 'icecat_datafeed_queue';

    protected $_cacheTag = 'icecat_datafeed_queue';

    protected $_eventPrefix = 'icecat_datafeed_queue';

    protected function _construct()
    {
        $this->_init('Icecat\DataFeed\Model\ResourceModel\IcecatDatafeedQueue');
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
