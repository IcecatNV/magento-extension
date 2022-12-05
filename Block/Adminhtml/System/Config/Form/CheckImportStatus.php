<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CheckImportStatus extends Field
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icecat/datafeed/ajaxnewimport.phtml');
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

   /**
    * Get the button and scripts contents
    *
    * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
    * @return string
    */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<strong style="color:#0a0;" id="progress">Checking import status...</strong>';
        return $html;
    }
}
