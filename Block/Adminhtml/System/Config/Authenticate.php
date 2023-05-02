<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\System\Config;

use Icecat\DataFeed\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Authenticate extends Field
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

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {       
        $response = $this->data->getUserSessionId();
        $validateAccessToken = $this->data->validateToken();
        $html = '';
        if (array_key_exists("Data",$response)){
            if ($validateAccessToken['httpcode'] == "400") {
                $html .= '<strong style="color:#D40707;">' . json_decode($validateAccessToken['response'])->Message . '</strong>';
            }else{
                $html .= '<strong style="color:#0a0;">User Authenticated </strong>';
            }
        }else{
            $html .= '<strong style="color:#D40707;">' . (string)$response['Message'] . '</strong>';
        }
        return $html;
    }

    /**
     * Returns configuration fields required to create store
     *
     * @return array
     * @since 100.1.0
     */
    protected function _getFieldMapping()
    {
        return [
            'username'      => 'datafeed_authentication_username',
            'password'      => 'datafeed_authentication_password',
            'access_token'  => 'datafeed_authentication_access_token'
        ];
    }
}
