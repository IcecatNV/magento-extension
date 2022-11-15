<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\Config\Backend\Serialized;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class ArraySerialized extends \Magento\Config\Model\Config\Backend\Serialized
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        AttributeRepositoryInterface $attributeRepository,
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        array $data = [],
        Json $serializer = null
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data, $serializer);
    }

    /**
     * @return ArraySerialized
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        /*foreach ($value as $data)
        {
            if ($data['override_attribute'])
            {
                try {
                    $attribute = $this->attributeRepository->get(Product::ENTITY, $data['new_attribute_code']);
                } catch (NoSuchEntityException $exception)
                {
                    $alreadyExistAttribute = $this->attributeRepository->get(Product::ENTITY, $data['attribute']);
                    if ($alreadyExistAttribute->getAttributeId())
                    {
                        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
                        $eavSetup->addAttribute(Product::ENTITY, $data['new_attribute_code'], [
                            'group' => 'Icecat Attribute',
                            'type' => $alreadyExistAttribute->getFrontendInput(),
                            'backend' => $alreadyExistAttribute->getBackendType(),
                            'frontend' => '',
                            'label' => $data['new_attribute_name'],
                            'input' => 'text',
                            'class' => $alreadyExistAttribute->getFrontendClass(),
                            'source' => $alreadyExistAttribute->getSourceModel(),
                            'global' => $alreadyExistAttribute->getData('is_global'),
                            'visible' => (bool)$alreadyExistAttribute->getData('is_visible'),
                            'required' => (bool)$alreadyExistAttribute->getIsRequired(),
                            'user_defined' => (bool)$alreadyExistAttribute->getIsUserDefined(),
                            'default' => $alreadyExistAttribute->getDefaultValue(),
                            'searchable' => (bool)$alreadyExistAttribute->getData('is_searchable'),
                            'filterable' => (bool)$alreadyExistAttribute->getData('is_filterable'),
                            'comparable' => (bool)$alreadyExistAttribute->getData('is_comparable'),
                            'visible_on_front' => (bool)$alreadyExistAttribute->getData('is_visible_on_front'),
                            'used_in_product_listing' => (bool)$alreadyExistAttribute->getData('used_in_product_listing'),
                            'unique' => (bool)$alreadyExistAttribute->getIsUnique(),
                            'apply_to' => $alreadyExistAttribute->getData('apply_to'),
                        ]);
                    }
                }
            }
        }*/

        if (is_array($value)) {
            unset($value['__empty']);
        }
        $this->setValue($value);
        return parent::beforeSave();
    }
}
