<?php

namespace Icecat\DataFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductAttachment extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('icecat_product_attachment', 'id');
    }
}
