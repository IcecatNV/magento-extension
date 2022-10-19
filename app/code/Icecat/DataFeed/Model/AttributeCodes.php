<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AttributeCodes
{
    // Text Type Attribute
    const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON = 'icecat_info_modify_on';
    const ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE = 'Icecat Info Modify On';
    const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER = 'icecat_disclaimer';
    const ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE = 'Icecat Disclaimer';
    const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY = 'icecat_warranty';
    const ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE = 'Icecat Warranty';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY = 'icecat_product_family';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE = 'Icecat Product Family';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE = 'icecat_product_title';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE = 'Icecat Product Title';
    const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME = 'icecat_long_product_name';
    const ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE = 'Icecat Long Product Name';

    // Text Editor Attribute
    const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION = 'icecat_specification';
    const ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE = 'Icecat Specifications';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES = 'icecat_product_stories';
    const ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE = 'Icecat Product Stories';
    const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY = 'icecat_reason_to_buy';
    const ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE = 'Icecat Reasons To Buy';
    const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS = 'icecat_bullet_points';
    const ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE = 'Icecat Bullet Points';

    const TEXT_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON => [self::ICECAT_PRODUCT_ATTRIBUTE_MODIFY_ON_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER => [self::ICECAT_PRODUCT_ATTRIBUTE_DISCLAIMER_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY => [self::ICECAT_PRODUCT_ATTRIBUTE_WARRANTY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_FAMILY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_TITLE_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME => [self::ICECAT_PRODUCT_ATTRIBUTE_LONG_PRODUCT_NAME_TITLE, ScopedAttributeInterface::SCOPE_STORE],
    ];

    const EDITOR_ATTRIBUTES = [
        self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION => [self::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES => [self::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY => [self::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY_TITLE, ScopedAttributeInterface::SCOPE_STORE],
        self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS => [self::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS_TITLE, ScopedAttributeInterface::SCOPE_STORE]
    ];
}
