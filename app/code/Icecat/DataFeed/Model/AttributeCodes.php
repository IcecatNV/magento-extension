<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AttributeCodes
{
    // Text Type Attribute
    protected const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON = 'icecat_info_modify_on';
    protected const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE = 'Icecat Info Modify On';
    protected const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER = 'icecat_disclaimer';
    protected const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE = 'Icecat Disclaimer';
    protected const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY = 'icecat_warranty';
    protected const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE = 'Icecat Warranty';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY = 'icecat_product_family';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE = 'Icecat Product Family';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE = 'icecat_product_title';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE = 'Icecat Product Title';
    protected const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME = 'icecat_long_product_name';
    protected const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE = 'Icecat Long Product Name';

    // Text Editor Attribute
    public const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION = 'icecat_specification';
    protected const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE = 'Icecat Specifications';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES = 'icecat_product_stories';
    protected const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE = 'Icecat Product Stories';
    public const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY = 'icecat_reason_to_buy';
    public const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE = 'Icecat Reasons To Buy';
    protected const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS = 'icecat_bullet_points';
    protected const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE = 'Icecat Bullet Points';

    protected const TEXT_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON => [self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER => [self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY => [self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME => [self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE, ScopedAttributeInterface::SCOPE_STORE],
    ];

    protected const EDITOR_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION => [self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY => [self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS => [self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE, ScopedAttributeInterface::SCOPE_STORE]
    ];
}
