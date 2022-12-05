<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class IcecatAttributeColumn extends Select
{
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

    /**
     * Set "style" for <select> element
     *
     */
    public function getExtraParams()
    {
        return 'style="width:200px"';
    }

    /**
     * Set "class" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setClass($class)
    {
        return $this->setData('class', $class);
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
        return [
            ['label' => 'Select Attribute', 'value' => ''],
            ['label' => 'Product Name', 'value' => 'GeneralInfo-ProductName'],
            ['label' => 'Long Description', 'value' => 'GeneralInfo-Description-LongDesc'],
            ['label' => 'Short Description', 'value' => 'GeneralInfo-SummaryDescription-ShortSummaryDescription'],
            ['label' => 'Product Title', 'value' => 'GeneralInfo-Title'],
            ['label' => 'Product Long Name', 'value' => 'GeneralInfo-Description-LongProductName'],
            ['label' => 'Info Modified On', 'value' => 'GeneralInfo-ReleaseDate'],
            ['label' => 'Disclaimer', 'value' => 'GeneralInfo-Description-Disclaimer'],
            ['label' => 'Warranty', 'value' => 'GeneralInfo-Description-WarrantyInfo'],
            ['label' => 'Product Family', 'value' => 'GeneralInfo-ProductFamily-Value'],
        ];
    }
}
