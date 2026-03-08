<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Name
{
    public function __construct(public readonly string $name) {}
}
