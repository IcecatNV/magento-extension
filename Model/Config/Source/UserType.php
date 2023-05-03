<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\Config\Source;

use Magento\Eav\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Option\ArrayInterface;
use Icecat\DataFeed\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

class UserType implements ArrayInterface
{
    private Config $config;
    protected Data $data;
    protected $configWriter;
    public $scopeConfig;
    protected $cacheTypeList;
    protected $cacheFrontendPool;

    CONST XML_PATH_ICECAT_DATAFEED_API_USERTYPE = 'datafeed/authentication/user_type';
    CONST XML_PATH_ICECAT_DATAFEED_API_Content_token = 'datafeed/authentication/content_token';
    CONST XML_PATH_ICECAT_DATAFEED_API_App_key = 'datafeed/authentication/app_key';
    /**
     * @param Config $config
     */
    public function __construct(
        Config $config,
        Data $data,
        WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        TypeListInterface $cacheTypeList, 
        Pool $cacheFrontendPool
    ) {
        $this->config = $config;
        $this->data = $data;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    public function toOptionArray()
    {
		$subscriptionLevel = $this->data->getUserType();
        if(!empty($subscriptionLevel) && $subscriptionLevel == 'full'){
            $this->setUserTypeValue(1);
            $this->flushCache();
            return [['value' =>1 , 'label' => __('Yes')],['value' =>0 , 'label' => __('No')]];
        }else if(!empty($subscriptionLevel) && $subscriptionLevel == 'open'){
            $this->setUserTypeValue(0);
            $this->flushCache();
            return [['value' =>0 , 'label' => __('No')],['value' =>1 , 'label' => __('Yes')]];
        }else{
            return [['value' =>0 , 'label' => __('No')],['value' =>1 , 'label' => __('Yes')]];
        }
    }

    public function setUserTypeValue($value)
    {
        $this->configWriter->save(self::XML_PATH_ICECAT_DATAFEED_API_USERTYPE, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
        if($value==0){
            $this->configWriter->save(self::XML_PATH_ICECAT_DATAFEED_API_Content_token, '', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->configWriter->save(self::XML_PATH_ICECAT_DATAFEED_API_App_key, '', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
        }
    }

    public function flushCache()
    {
        $_types = [
            'config'
        ];
    
        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}