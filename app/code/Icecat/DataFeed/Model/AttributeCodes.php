<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AttributeCodes
{
    // Text Type Attribute
    public const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON = 'icecat_info_modify_on';
    public const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE = 'Icecat Info Modify On';
    public const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER = 'icecat_disclaimer';
    public const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE = 'Icecat Disclaimer';
    public const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY = 'icecat_warranty';
    public const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE = 'Icecat Warranty';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY = 'icecat_product_family';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE = 'Icecat Product Family';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE = 'icecat_product_title';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE = 'Icecat Product Title';
    public const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME = 'icecat_long_product_name';
    public const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE = 'Icecat Long Product Name';

    // Text Editor Attribute
    public const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION = 'icecat_specifications';
    public const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE = 'Icecat Specifications';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES = 'icecat_product_stories';
    public const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE = 'Icecat Product Stories';
    public const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY = 'icecat_reasons_to_buy';
    public const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE = 'Icecat Reasons To Buy';
    public const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS = 'icecat_bullet_points';
    public const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE = 'Icecat Bullet Points';

    public const TEXT_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON => [self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER => [self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY => [self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME => [self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE, ScopedAttributeInterface::SCOPE_STORE],
    ];

    public const EDITOR_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION => [self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY => [self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS => [self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE, ScopedAttributeInterface::SCOPE_STORE]
    ];
}
