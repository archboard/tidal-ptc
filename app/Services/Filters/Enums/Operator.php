<?php

namespace App\Services\Filters\Enums;

enum Operator: string
{
    case contains = 'contains';
    case not_contains = 'not_contains';
    case starts_with = 'starts';
    case not_starts_with = 'not_starts';
    case ends_with = 'ends';
    case not_ends_with = 'not_ends';
    case in = 'in';
    case not_in = 'not_in';
    case equals = '=';
    case not_equals = '!=';
    case greater_than = '>';
    case greater_than_or_equal_to = '>=';
    case less_than = '<';
    case less_than_or_equal_to = '<=';

    public function label(): string
    {
        return match ($this) {
            self::contains => __('Contains'),
            self::not_contains => __("Doesn't contain"),
            self::starts_with => __('Starts with'),
            self::not_starts_with => __("Doesn't start with"),
            self::ends_with => __('Ends with'),
            self::not_ends_with => __("Doesn't end with"),
            self::in => __('In'),
            self::not_in => __('Not in'),
            self::equals => __('Equals'),
            self::not_equals => __("Doesn't equal"),
            self::greater_than => __('Greater than'),
            self::greater_than_or_equal_to => __('Greater than or equal to'),
            self::less_than => __('Less than'),
            self::less_than_or_equal_to => __('Less than or equal to'),
        };
    }

    public function transformValue(mixed $value): mixed
    {
        return match ($this) {
            self::contains, self::not_contains => "%{$value}%",
            self::starts_with, self::not_starts_with => "{$value}%",
            self::ends_with, self::not_ends_with => "%{$value}",
            self::in, self::not_in => $this->valueToArray($value),
            default => $value,
        };
    }

    public function getBuilderFunction(): string
    {
        return match ($this) {
            self::in => 'whereIn',
            self::not_in => 'whereNotIn',
            default => 'where',
        };
    }

    public function getSqlOperator(string $grammar = 'pgsql'): ?string
    {
        if (! $this->usesSqlOperator()) {
            return null;
        }

        $like = match ($grammar) {
            'pgsql' => 'ilike',
            default => 'like',
        };

        return match ($this) {
            self::contains, self::starts_with, self::ends_with => $like,
            self::not_contains, self::not_starts_with, self::not_ends_with => "not {$like}",
            default => $this->value,
        };
    }

    public function valueToArray(array|string|int $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return array_map('trim', explode(',', (string) $value));
    }

    public function usesSqlOperator(): bool
    {
        return match ($this) {
            self::in, self::not_in => false,
            default => true,
        };
    }
}
