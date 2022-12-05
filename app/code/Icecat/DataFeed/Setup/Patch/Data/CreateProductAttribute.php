<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Setup\Patch\Data;

use Icecat\DataFeed\Model\AttributeCodes;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class CreateProductAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    private $schemaSetup;

    protected const ICECAT_TABLES = [
        'icecat_product_attachment',
        'icecat_product_review',
        'icecat_datafeed_queue',
        'icecat_datafeed_queue_log',
        'icecat_datafeed_queue_archive',
        'icecat_queue_scheduler'
    ];
    /**
     * @var Config|ConfigInterface
     */
    private $config;
    private CategoryRepository $categoryRepository;
    protected $scopeConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param SchemaSetupInterface $schemaSetup
     * @param ConfigInterface $config
     * @param CategoryRepository $categoryRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        SchemaSetupInterface $schemaSetup,
        ConfigInterface $config,
        CategoryRepository $categoryRepository,
        Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->schemaSetup = $schemaSetup;
        $this->config = $config;
        $this->categoryRepository = $categoryRepository;
        $registry->register('isSecureArea', true);
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
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Adding Icecat Attribute Group
        $groupName = 'Icecat Product Content';
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 15);
        }

        foreach (AttributeCodes::TEXT_ATTRIBUTES as $key => $value) {
            $eavSetup->addAttribute(Product::ENTITY, $key, [
                'group' => $groupName,
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => $value[0],
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => $value[1],
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
            ]);
        }

        foreach (AttributeCodes::EDITOR_ATTRIBUTES as $key => $value) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $key,
                [
                    'group' => $groupName,
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => $value[0],
                    'input' => 'textarea',
                    'class' => '',
                    'source' => '',
                    'global' => $value[1],
                    'wysiwyg_enabled' => true,
                    'is_html_allowed_on_front' => true,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            'icecat_updated_time',
            [
                'group' => $groupName,
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => 'Icecat Updated Time',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'is_html_allowed_on_front' => true,
                'visible' => false,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'icecat_icecat_id',
            [
                'group' => $groupName,
                'type' => 'text',
                'backend' => '',
                'frontend' => '',
                'label' => 'Icecat Id',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'is_html_allowed_on_front' => true,
                'visible' => false,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Deleting category
        /* $connection = $this->moduleDataSetup->getConnection();
        $table = $connection->getTableName('core_config_data');
        $category = $connection->getConnection()
            ->query('SELECT value FROM ' . $table . ' WHERE path = "datafeed/icecat/root_category_id"')
            ->fetch(); */
        $categoryId = $this->_scopeConfig->getValue('datafeed/icecat/root_category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!empty($categoryId)) {
            //$categoryId = $category['value'];
            $category = $this->categoryRepository->get($categoryId);
            $subCategories = $category->getChildrenCategories();
            if ($subCategories->getData() > 0) {
                foreach ($subCategories as $subCategory) {
                    if ($subCategory->hasChildren()) {
                        $childCategoryObj = $this->categoryRepository->get($subCategory->getId());
                        $childSubcategories = $childCategoryObj->getChildrenCategories();
                        foreach ($childSubcategories as $childSubcategory) {
                            $childSubcategory->delete();
                        }
                    }
                    $subCategory->delete();
                }
            }
        }

        $this->categoryRepository->deleteByIdentifier($categoryId);
        $connection->getConnection()
            ->query('Delete FROM ' . $table . ' WHERE path like "%datafeed%"')
            ->fetch();

        // Deleting patch data from patch_list table
        $patchTable = $connection->getTableName('patch_list');
        $connection->getConnection()
            ->query('Delete FROM ' . $patchTable . ' WHERE patch_name like "%Icecat%"')
            ->fetch();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        foreach (AttributeCodes::TEXT_ATTRIBUTES as $key => $value) {
            $eavSetup->removeAttribute($entityTypeId, $key);
        }

        foreach (AttributeCodes::EDITOR_ATTRIBUTES as $key => $value) {
            $eavSetup->removeAttribute($entityTypeId, $key);
        }

        $eavSetup->removeAttribute($entityTypeId, 'icecat_updated_time');
        $eavSetup->removeAttribute($entityTypeId, 'icecat_icecat_id');

        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Icecat Attributes');
        }

        $installer = $this->schemaSetup;
        $installer->startSetup();
        foreach (self::ICECAT_TABLES as $table) {
            if ($installer->tableExists($table)) {
                $installer->getConnection()->dropTable($installer->getTable($table));
            }
        }
        $installer->endSetup();

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
