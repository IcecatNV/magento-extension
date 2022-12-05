<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class ProductAttachment extends AbstractModel implements IdentityInterface
{
    protected const CACHE_TAG = 'icecat_product_attachment';

    protected $_cacheTag = 'icecat_product_attachment';

    protected $_eventPrefix = 'icecat_product_attachment';

    protected function _construct()
    {
        $this->_init('Icecat\DataFeed\Model\ResourceModel\ProductAttachment');
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
