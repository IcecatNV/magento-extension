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

    protected const ICECAT_XML_URL = 'https://data.icecat.biz/xml_s3/xml_server3.cgi?ean_upc=5397063929863;lang=en;output=productxml';

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
     * Test the API connection and report common errors.
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function testApi()
    {
        if ($this->data->getIsModuleEnabled()) {
            try {
                if (!empty($this->data->getUsername()) && !empty($this->data->getPassword()) && !empty($this->data->getAccessToken())) {
                    $username_password = base64_encode($this->data->getUsername() . ':' . $this->data->getPassword());
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => self::ICECAT_XML_URL,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Basic ' . $username_password
                        ],
                    ]);
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $finalResponse = strpos(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), 'ErrorMessage') > 0 ? 1 : 0;

                    if ($finalResponse == 0) {
                        $validateAccessToken = $this->validateUserWithToken();
                        if ($validateAccessToken['httpcode'] == 200) {
                            $response   = __('User Authenticated');
                        } else {
                            $message    = json_decode($validateAccessToken['response'])->Message;
                            $response   = $message;
                        }
                    } else {
                        $response = __('Invalid Username or Password');
                    }
                } else {
                    $response = __('Please enter username, password and access token');
                }
            } catch (\Exception $e) {
                $response = __('Something went wrong');
            }
            return $response;
        }
        return __('Module is not enabled');
    }

    public function validateUserWithToken()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://live.icecat.biz/api/?UserName=' . $this->data->getUsername() . 'type&Language=en&gtin=5397063929863',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'api-token: ' . $this->data->getAccessToken()
            ],

        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [
            'response' => $response,
            'httpcode' => $httpcode
        ];
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
        $html = (string)$this->testApi();

        if (strpos($html, 'Authenticated') !== false) {
            $html = '<strong style="color:#0a0;">' . $html . '</strong>';
        } else {
            $html = '<strong style="color:#D40707;">' . $html . '</strong>';
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
