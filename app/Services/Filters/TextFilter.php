<?php

namespace App\Services\Filters;

use App\Services\Filters\Enums\Operator;

class TextFilter extends BaseFilter
{
    public array $operators = [
        Operator::contains,
        Operator::not_contains,
        Operator::starts_with,
        Operator::not_starts_with,
        Operator::ends_with,
        Operator::not_ends_with,
    ];
}
