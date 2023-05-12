<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\System\Config;

use Icecat\DataFeed\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class IcecatLinks extends Field
{
    private Data $data;

    /**
     * @param Context $context
     * @param Data $data
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Data $data
    ) {
        parent::__construct($context);
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        $subscriptionLevel = $this->data->getUserType();
        if (!empty($this->data->getUsername()) && !empty($this->data->getPassword()) ) {        
            if ($subscriptionLevel != 'full') {
                $html .= '<p><a href="https://icecat.biz/en/menu/contacts/index.html" target="_blank">Upgrade To Full Icecat</a></p>';
            }
        }
        $html .= '<p><a href="https://icecat.biz/en/registration" target="_blank">Register with Icecat</a></p> <p><a href="https://icecat.biz/forgot" target="_blank">Forget Password</a></p> <p><a href="https://icecat.biz/mk/menu/contacts/index.html" target="_blank">Contact Us</a>';
        return $html;
    }
}
