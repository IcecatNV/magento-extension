<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\Form\Field;

use Magento\Eav\Model\Config;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class AttributeColumn extends Select
{
    /**
     * @var Config
     **/
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function getExtraParams()
    {
        return 'style="width:200px"';
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        $options[] = ['label' => 'Select Attribute', 'value' => ''];
        $allProductAttributes = array_keys($this->config->getEntityAttributes('catalog_product'));
        $allProductAttributes = $this->getRemoveOption($allProductAttributes);
        foreach ($allProductAttributes as $attributeCode) {
            $options[] = ['value' => $attributeCode, 'label' => $this->config
                ->getAttribute('catalog_product', $attributeCode)
                ->getDefaultFrontendLabel()];
        }
        return $options;
    }

    private function getRemoveOption($productAttributes)
    {
        $staticAttributes = ['category_ids','pdf','icecat_pdf','specification'];
        return array_diff($productAttributes, $staticAttributes);
    }
}
