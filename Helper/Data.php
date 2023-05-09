<?php

namespace Icecat\DataFeed\Helper;

use Icecat\DataFeed\Model\IcecatDatafeedQueueLog;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var StoreManagerInterface
     * */
    protected $_storeManager;

    /**
     * @var Json
     * */
    protected $serialize;
    protected $_icecatQueueLog;

    protected const XML_PATH_ICECAT_DATAFEED_ENABLED = 'datafeed/general/enable';

    protected const XML_PATH_ICECAT_DATAFEED_AUTH_USERNAME = 'datafeed/authentication/username';
    protected const XML_PATH_ICECAT_DATAFEED_AUTH_PASSWORD = 'datafeed/authentication/password';
    protected const XML_PATH_ICECAT_DATAFEED_API_ACCESS_TOKEN = 'datafeed/authentication/access_token';
    protected const XML_PATH_ICECAT_DATAFEED_API_CONTENT_TOKEN = 'datafeed/authentication/content_token';
    protected const XML_PATH_ICECAT_DATAFEED_API_APP_KEY = 'datafeed/authentication/app_key';

    protected const XML_PATH_ICECAT_STORE_CONFIGURATION = 'datafeed/icecat_store_config/stores';

    protected const XML_PATH_ICECAT_GTIN_MAPPING = 'datafeed/gtin_fetch_type/gtin';
    protected const XML_PATH_ICECAT_PRODUCT_CODE_MAPPING = 'datafeed/product_brand_fetch_type/product_code';
    protected const XML_PATH_ICECAT_BRAND_MAPPING = 'datafeed/product_brand_fetch_type/brand';
    protected const XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_STATUS = 'datafeed/product_attributes/icecat_categorization/status';
    protected const XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_INCLUDE_IN_MENU = 'datafeed/product_attributes/icecat_categorization/include_in_menu';

    protected const XML_PATH_ICECAT_IMPORT_IMAGES = 'datafeed/product_attributes/media/import_images';
    protected const XML_PATH_ICECAT_IMPORT_VIDEOS = 'datafeed/product_attributes/media/import_videos';
    protected const XML_PATH_ICECAT_IMPORT_PDF = 'datafeed/product_attributes/media/import_pdf';
    protected const XML_PATH_ICECAT_PDF_ATTRIBUTE = 'datafeed/product_attributes/media/pdf_attribute';

    protected const XML_PATH_ICECAT_IMPORT_SPECIFICATION = 'datafeed/product_attributes/specification/import_specification';
    protected const XML_PATH_ICECAT_IMPORT_SPECIFICATION_ATTRIBUTE = 'datafeed/product_attributes/specification/specification_attribute';
    protected const XML_PATH_ICECAT_IMPORT_SPECIFICATION_HEADER_COLOR = 'datafeed/product_attributes/specification/specification_header_color';

    protected const XML_PATH_ICECAT_IMPORT_RELATED_PRODUCT = 'datafeed/product_attributes/other_fields/import_related_products';
    protected const XML_PATH_ICECAT_IMPORT_PRODUCT_STORIES = 'datafeed/product_attributes/other_fields/import_product_stories';
    protected const XML_PATH_ICECAT_IMPORT_PRODUCT_REVIEWS = 'datafeed/product_attributes/other_fields/import_product_reviews';
    protected const XML_PATH_ICECAT_IMPORT_REASON_TO_BUY = 'datafeed/product_attributes/other_fields/import_reasons_to_buy';
    protected const XML_PATH_ICECAT_IMPORT_BULLET_POINTS = 'datafeed/product_attributes/other_fields/import_bullet_points';

    protected const XML_PATH_ICECAT_DATAFEED_PRODUCT_ATTRIBUTE = 'datafeed/product_attributes/basic_fields/attributes';

    protected const XML_PATH_LANGAUGE_CONFIG = 'general/locale/code';

    protected const XML_PATH_TIMEZONE = 'general/locale/timezone';
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Json $serialize
     * @param IcecatDatafeedQueueLog $icecatQueueLog
     * @param Repository $attributeRepository
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        Json                  $serialize,
        IcecatDatafeedQueueLog $icecatQueueLog,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Repository $attributeRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->serialize = $serialize;
        $this->_icecatQueueLog = $icecatQueueLog;
        $this->_scopeConfig = $scopeConfig;
        $this->attributeRepository = $attributeRepository;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function getIsModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ICECAT_DATAFEED_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_DATAFEED_AUTH_USERNAME);
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_DATAFEED_AUTH_PASSWORD);
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_DATAFEED_API_ACCESS_TOKEN);
    }

    /**
     * @return mixed
     */
    public function getContentToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_DATAFEED_API_CONTENT_TOKEN);
    }
    
	/**
     * @return mixed
     */
    public function getAppKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_DATAFEED_API_APP_KEY);
    }

    /**
     * @return array|bool|float|int|mixed|string|void|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductAttributes()
    {
        $productAttributes = $this->scopeConfig->getValue(
            self::XML_PATH_ICECAT_DATAFEED_PRODUCT_ATTRIBUTE,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
        if ($productAttributes == '' || $productAttributes == null) {
            return;
        }

        return $this->serialize->unserialize($productAttributes);
    }

    /**
     * @return mixed
     */
    public function getIcecatUri(Product $product, $language)
    {
        $username = $this->getUsername();
        if (!empty($this->getGTINCode())) {
            $gtinCode = $this->getGTINCode();
            $gtinCodeData = $product->getData($gtinCode);
            if (!empty($gtinCodeData)) {
                return '?UserName=' . $username . '&Language=' . $language . '&GTIN=' . $gtinCodeData;
            } elseif (!empty($this->getProductCode()) && !empty($this->getBrandCode())) {
                $productCode = $this->getProductCode();
                $brandCode = $this->getBrandCode();
                $productCodeData = $product->getData($productCode);
                $brandCodeData = $product->getData($brandCode);
                
                $attributeType = $this->attributeRepository->get($brandCode)->getFrontendInput();
                if ($attributeType == 'select') {
                    $brandCodeData  = $product->getAttributeText($brandCode);
                }

                if (!empty($productCodeData) && !empty($brandCodeData)) {
                    return '?UserName=' . $username . '&Language=' . $language . '&Brand=' . $brandCodeData . '&ProductCode=' . $productCodeData;
                }
            }
        } elseif (!empty($this->getProductCode()) && !empty($this->getBrandCode())) {
            $productCode = $this->getProductCode();
            $brandCode = $this->getBrandCode();
            $productCodeData = $product->getData($productCode);
            $brandCodeData = $product->getData($brandCode);

            $attributeType = $this->attributeRepository->get($brandCode)->getFrontendInput();
            if ($attributeType == 'select') {
                $brandCodeData  = $product->getAttributeText($brandCode);
            }
            
            if (!empty($productCodeData) && !empty($brandCodeData)) {
                return '?UserName=' . $username . '&Language=' . $language . '&Brand=' . $brandCodeData . '&ProductCode=' . $productCodeData;
            } elseif (!empty($this->getGTINCode())) {
                $gtinCode = $this->getGTINCode();
                $gtinCodeData = $product->getData($gtinCode);
                if (!empty($gtinCodeData)) {
                    return '?UserName=' . $username . '&Language=' . $language . '&GTIN=' . $gtinCodeData;
                }
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getProductCode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_PRODUCT_CODE_MAPPING);
    }

    /**
     * @return mixed
     */
    public function getBrandCode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_BRAND_MAPPING);
    }

    /**
     * @return mixed
     */
    public function getGTINCode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_GTIN_MAPPING);
    }

    public function isImportImagesEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_IMAGES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isImportMultimediaEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_VIDEOS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isImportPdfEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_PDF, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getPdfMappingAttribute()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_PDF_ATTRIBUTE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isImportSpecificationEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_SPECIFICATION, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isCategoryImportEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_STATUS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isCategoryIncludeInMenuEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_INCLUDE_IN_MENU, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getSpecificationAttribute()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_SPECIFICATION_ATTRIBUTE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getSpecificationHeaderColor()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_SPECIFICATION_HEADER_COLOR, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isReasonToBuyEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_REASON_TO_BUY, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isProductStoriesEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_PRODUCT_STORIES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isProductReviewEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_PRODUCT_REVIEWS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function isBulletPointsEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_BULLET_POINTS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getIcecatStoreConfig()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_STORE_CONFIGURATION);
    }

    public function getStoreLanguage($storeId)
    {
        $language = $this->scopeConfig->getValue(self::XML_PATH_LANGAUGE_CONFIG, ScopeInterface::SCOPE_STORE, $storeId);
        $lang = explode('_', $language);
        return $lang[0];
    }

    public function isImportRelatedProductEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ICECAT_IMPORT_RELATED_PRODUCT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function createIcecatQueueLog($data, $logId = null)
    {
        $model = $this->_icecatQueueLog->create();
        $model->load($logId);
        if (empty($model->getData())) {
            $model->addData($data);
        } else {
            $model->setData($data);
        }

        $saveData = $model->save();
        return $saveData;
    }

    public function getTimezone()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TIMEZONE);
    }
    public function validateToken()
    {
        $username = $this->_scopeConfig->getValue('datafeed/authentication/username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $accessToken = $this->_scopeConfig->getValue('datafeed/authentication/access_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://live.icecat.biz/api/?UserName=' . $username . 'type&Language=en&gtin=5397063929863',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'api-token: ' . $accessToken
            ],

        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $response= [
            'response' => $response,
            'httpcode' => $httpcode
        ];
    }

    public function getUserSessionId()
    {
        $uid= array( 
            'Login' => $this->getUsername(),
            'Password' => $this->getPassword(),
            'Session' => 'Rest'
        );
        $postData = json_encode($uid);

        if ($this->getIsModuleEnabled()) {
            try {
                if (!empty($this->getUsername()) && !empty($this->getPassword()) ) {
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://bo.icecat.biz/restful/v3/Session',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $postData,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = json_decode(curl_exec($curl),true);
                    curl_close($curl);
                } else {
                    $responseMessage = __('Please enter username and password');
                    $response = ['Code'=> 400 ,'Error' => 'Bad Request','Message'=> $responseMessage];
                }
            } catch (\Exception $e) {
                $responseMessage = __('Something went wrong');
                $response = ['Code'=> 400 ,'Error' => 'Bad Request','Message'=> $responseMessage];
            }
        }else{
            $responseMessage = __('Please Enable Module ');
            $response = ['Code'=> 400 ,'Error' => 'Bad Request','Message'=> $responseMessage];
        }
        
        return $response;
    }

    public function getUserType()
    {
        $usrSessionId = $this->getUserSessionId();
        $usertype = 0;
        if (!empty($usrSessionId) && !empty($usrSessionId['Data'])) {
            $sessionId = $usrSessionId['Data']['SessionId'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://icecat.biz/rest/user-profile?AccessKey=' . $sessionId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ULocation=WW%7Cen'
                ),
            ));
            $response = json_decode(curl_exec($curl),true);
            $usertype = $response['Data']['SubscriptionLevel'];
            curl_close($curl); // full or open
            if ($usertype == 1 || $usertype == 4){
                return $usertype = 'full';
            } else if($usertype == 5 || $usertype == 6){
                return $usertype = 'open';
            }           
        } else{
            return $usertype;
        }
    }
}
