<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class OnlyNewProduct extends Field
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icecat/datafeed/ajaxnewimport.phtml');
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $prod_button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'id' => 'icecat_button',
                'label' => 'Delta Import',
                'onclick' => 'javascript:import_new_prod_info(); return false;'
            ]);
        $buttons = $prod_button->toHtml();
        return $buttons;
    }
}
