<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Setup\Patch\Schema;

use Icecat\DataFeed\Model\AttributeCodes;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class ConfigPatch implements SchemaPatchInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    protected $scopeConfig;
    private $defaultConfigData = [
        'datafeed/general/enable' => 0,
        'datafeed/product_attributes/icecat_categorization/status' => '0',
        'datafeed/product_attributes/icecat_categorization/include_in_menu' => '1',
        'datafeed/product_attributes/media/import_images' => '0',
        'datafeed/product_attributes/media/import_videos' => '0',
        'datafeed/product_attributes/media/import_pdf' => '0',
        'datafeed/product_attributes/specification/import_specification' => '0',
        'datafeed/product_attributes/specification/specification_header_color' => '#EEF1FA',
        'datafeed/product_attributes/other_fields/import_related_products' => '0',
        'datafeed/product_attributes/other_fields/import_product_stories' => '0',
        'datafeed/product_attributes/other_fields/import_product_reviews' => '0',
        'datafeed/product_attributes/other_fields/import_reasons_to_buy' => '0',
        'datafeed/product_attributes/other_fields/import_bullet_points' => '0'
    ];

    private $defaultArrayConfigData = [
        'datafeed/product_attributes/basic_fields/attributes' => [
            '_1661750423166_166' => [
                'icecat_fields' => 'GeneralInfo-ProductName',
                'attribute' => ''
            ],
            '_1661750423166_167' => [
                'icecat_fields' => 'GeneralInfo-Description-LongDesc',
                'attribute' => ''
            ],
            '_1661750423166_168' => [
                'icecat_fields' => 'GeneralInfo-SummaryDescription-ShortSummaryDescription',
                'attribute' => ''
            ],
            '_1661750423166_169' => [
                'icecat_fields' => 'GeneralInfo-Title',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE
            ],
            '_1661750423166_170' => [
                'icecat_fields' => 'GeneralInfo-Description-LongProductName',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME
            ],
            '_1661750423166_171' => [
                'icecat_fields' => 'GeneralInfo-ProductFamily-Value',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY
            ],
            '_1661750423166_172' => [
                'icecat_fields' => 'GeneralInfo-Description-Disclaimer',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER
            ],
            '_1661750423166_173' => [
                'icecat_fields' => 'GeneralInfo-Description-WarrantyInfo',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY
            ],
            '_1661750423166_174' => [
                'icecat_fields' => 'GeneralInfo-ReleaseDate',
                'attribute' => AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON
            ]
        ]
    ];

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->config = $config;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serializeDefaultArrayConfigData();
        $this->mergeDefaultDataWithArrayData();
        $this->_scopeConfig = $scopeConfig;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //$connection = $this->moduleDataSetup->getConnection();
        //$table = $connection->getTableName('core_config_data');

        /* SET DEFAULT CONFIG DATA */

        /* $alreadyInserted = $connection->getConnection()
            ->query('SELECT path, value FROM ' . $table . ' WHERE path LIKE "datafeed_%"')
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
         */
        $alreadyInserted = $this->_scopeConfig->getValue('datafeed_%', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        //$this->scopeConfig->getValue('path/of/config', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        foreach ($this->defaultConfigData as $path => $value) {
            if (isset($alreadyInserted[$path])) {
                continue;
            }
            $this->config->saveConfig($path, $value, 'default', 0);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    private function serializeDefaultArrayConfigData()
    {
        $serializeMethod = 'json_encode';

        foreach ($this->defaultArrayConfigData as $path => $array) {
            $this->defaultArrayConfigData[$path] = $serializeMethod($array);
        }
    }

    private function mergeDefaultDataWithArrayData()
    {
        $this->defaultConfigData = array_merge($this->defaultConfigData, $this->defaultArrayConfigData);
    }
}
