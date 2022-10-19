<?php

namespace Icecat\DataFeed\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;

class CronInfo extends Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icecat/datafeed/croninfo.phtml');
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
