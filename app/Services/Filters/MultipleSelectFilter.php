<?php

namespace App\Services\Filters;

use App\Services\Filters\Enums\Component;
use App\Services\Filters\Enums\Operator;

class MultipleSelectFilter extends BaseFilter
{
    public array $operators = [
        Operator::in,
        Operator::not_in,
    ];

    public Component $component = Component::checkbox_group;

    /** @return array<int, mixed> */
    public function defaultValue(): array
    {
        return [];
    }
}
