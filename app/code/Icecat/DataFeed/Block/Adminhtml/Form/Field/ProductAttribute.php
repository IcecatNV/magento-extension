<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Icecat\DataFeed\Block\Adminhtml\Form\Field\AttributeColumn;
use Icecat\DataFeed\Block\Adminhtml\Form\Field\OverrideArrtibuteColumn;
use Icecat\DataFeed\Block\Adminhtml\Form\Field\IcecatAttributeColumn;

class ProductAttribute extends AbstractFieldArray
{
    /**
     * @var AttributeColumn
     */
    private $attributeRenderer;

    /**
     * @var OverrideArrtibuteColumn
     */
    private $overrideAttributeRenderer;

    /**
     * @var IcecatAttributeColumn
     */
    private $icecatAttributes;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'icecat_fields',
            [
                'label' => __('Icecat Attributes'),
                'renderer' => $this->getIcecatAttributes()
            ]
        );

        $this->addColumn(
            'attribute',
            [
                'label' => __('Magento Attributes'),
                'renderer' => $this->getProductAttributes(),
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $productAttribute = $row->getAttribute();
        if ($productAttribute !== null) {
            $options['option_' . $this->getProductAttributes()->calcOptionHash($productAttribute)] = 'selected="selected"';
        }

        $overrideAttribute = $row->getOverrideAttribute();
        if ($overrideAttribute !== null) {
            $options['option_' . $this->getIsOverideDropDown()->calcOptionHash($overrideAttribute)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return AttributeColumn
     * @throws LocalizedException
     */
    private function getProductAttributes()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                AttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->attributeRenderer->setClass('required-entry');
        }
        return $this->attributeRenderer;
    }

    /**
     * @return OverrideArrtibuteColumn
     * @throws LocalizedException
     */
    private function getIsOverideDropDown()
    {
        if (!$this->overrideAttributeRenderer) {
            $this->overrideAttributeRenderer = $this->getLayout()->createBlock(
                OverrideArrtibuteColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->overrideAttributeRenderer;
    }

    /**
     * @return OverrideArrtibuteColumn
     * @throws LocalizedException
     */
    private function getIcecatAttributes()
    {
        if (!$this->icecatAttributes) {
            $this->icecatAttributes = $this->getLayout()->createBlock(
                IcecatAttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->icecatAttributes->setClass('required-entry');
        }
        return $this->icecatAttributes;
    }
}
