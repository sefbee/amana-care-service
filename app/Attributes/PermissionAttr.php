<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)] // Specifies that this attribute is for methods
class PermissionAttr
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null
    ) {}
}
