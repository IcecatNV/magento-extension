<?php

namespace Icecat\DataFeed\Helper;

use Icecat\DataFeed\Model\IcecatDatafeedQueueLog;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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

    const XML_PATH_ICECAT_DATAFEED_ENABLED = 'datafeed/general/enable';

    const XML_PATH_ICECAT_DATAFEED_AUTH_USERNAME = 'datafeed/authentication/username';
    const XML_PATH_ICECAT_DATAFEED_AUTH_PASSWORD = 'datafeed/authentication/password';
    const XML_PATH_ICECAT_DATAFEED_API_ACCESS_TOKEN = 'datafeed/authentication/access_token';

    const XML_PATH_ICECAT_STORE_CONFIGURATION = 'datafeed/icecat_store_config/stores';

    const XML_PATH_ICECAT_GTIN_MAPPING = 'datafeed/gtin_fetch_type/gtin';
    const XML_PATH_ICECAT_PRODUCT_CODE_MAPPING = 'datafeed/product_brand_fetch_type/product_code';
    const XML_PATH_ICECAT_BRAND_MAPPING = 'datafeed/product_brand_fetch_type/brand';

    const XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_STATUS = 'datafeed/product_attributes/icecat_categorization/status';
    const XML_PATH_ICECAT_IMPORT_ICECAT_CATEGORISATION_INCLUDE_IN_MENU = 'datafeed/product_attributes/icecat_categorization/include_in_menu';

    const XML_PATH_ICECAT_IMPORT_IMAGES = 'datafeed/product_attributes/media/import_images';
    const XML_PATH_ICECAT_IMPORT_VIDEOS = 'datafeed/product_attributes/media/import_videos';
    const XML_PATH_ICECAT_IMPORT_PDF = 'datafeed/product_attributes/media/import_pdf';
    const XML_PATH_ICECAT_PDF_ATTRIBUTE = 'datafeed/product_attributes/media/pdf_attribute';

    const XML_PATH_ICECAT_IMPORT_SPECIFICATION = 'datafeed/product_attributes/specification/import_specification';
    const XML_PATH_ICECAT_IMPORT_SPECIFICATION_ATTRIBUTE = 'datafeed/product_attributes/specification/specification_attribute';
    const XML_PATH_ICECAT_IMPORT_SPECIFICATION_HEADER_COLOR = 'datafeed/product_attributes/specification/specification_header_color';

    const XML_PATH_ICECAT_IMPORT_RELATED_PRODUCT = 'datafeed/product_attributes/other_fields/import_related_products';
    const XML_PATH_ICECAT_IMPORT_PRODUCT_STORIES = 'datafeed/product_attributes/other_fields/import_product_stories';
    const XML_PATH_ICECAT_IMPORT_PRODUCT_REVIEWS = 'datafeed/product_attributes/other_fields/import_product_reviews';
    const XML_PATH_ICECAT_IMPORT_REASON_TO_BUY = 'datafeed/product_attributes/other_fields/import_reasons_to_buy';
    const XML_PATH_ICECAT_IMPORT_BULLET_POINTS = 'datafeed/product_attributes/other_fields/import_bullet_points';

    const XML_PATH_ICECAT_DATAFEED_PRODUCT_ATTRIBUTE = 'datafeed/product_attributes/basic_fields/attributes';

    const XML_PATH_LANGAUGE_CONFIG = 'general/locale/code';

    const XML_PATH_TIMEZONE = 'general/locale/timezone';
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Json $serialize
     * @param IcecatDatafeedQueueLog $icecatQueueLog
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        Json                  $serialize,
        IcecatDatafeedQueueLog $icecatQueueLog
    ) {
        $this->_storeManager = $storeManager;
        $this->serialize = $serialize;
        $this->_icecatQueueLog = $icecatQueueLog;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

                if (!empty($productCodeData) && !empty($brandCodeData)) {
                    return '?UserName=' . $username . '&Language=' . $language . '&Brand=' . $brandCodeData . '&ProductCode=' . $productCodeData;
                }
            }
        } elseif (!empty($this->getProductCode()) && !empty($this->getBrandCode())) {
            $productCode = $this->getProductCode();
            $brandCode = $this->getBrandCode();
            $productCodeData = $product->getData($productCode);
            $brandCodeData = $product->getData($brandCode);

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
}
