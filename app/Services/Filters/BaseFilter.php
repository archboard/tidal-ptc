<?php

namespace App\Services\Filters;

use App\Services\Filters\Enums\Component;
use App\Services\Filters\Enums\Operator;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;
use Spatie\QueryBuilder\Filters\Filter;

abstract class BaseFilter implements Filter
{
    use Conditionable;

    public bool $showAsAvailable = true;

    public Component $component = Component::text;

    public array $operators = [];

    public array $componentProps = [];

    public Closure $callback;

    public mixed $currentValue = null;

    public Operator $operator;

    final public function __construct(public string $key, public string $label) {}

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $callback = isset($this->callback)
            ? $this->callback
            : $this->defaultCallback();

        $callback($query, $value);
    }

    public static function make(string $key = '', string $label = ''): static
    {
        return new static($key, $label);
    }

    public function getOperators(): array
    {
        return array_reduce($this->operators, function ($carry, Operator $operator) {
            $carry[$operator->value] = $operator->label();

            return $carry;
        }, []);
    }

    public function options(array $options): static
    {
        $this->componentProps = [
            ...$this->componentProps,
            'options' => $options,
        ];

        return $this;
    }

    public function using(Closure $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function applyWith(Closure $callback): static
    {
        return $this->using($callback);
    }

    public function withValue(mixed $value): static
    {
        $this->currentValue = $value;

        return $this;
    }

    public function withCurrentValue(mixed $value): static
    {
        return $this->withValue($value);
    }

    public function hasValue(mixed $value): static
    {
        return $this->withValue($value);
    }

    public function withOperator(string|Operator $operator): static
    {
        $this->operator = $operator instanceof Operator
            ? $operator
            : Operator::from($operator);

        return $this;
    }

    /**
     * Determines whether to include this filter in the available filters list.
     */
    public function hide(bool $available = false): static
    {
        $this->showAsAvailable = $available;

        return $this;
    }

    public function withComponent(Component $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function getSqlOperator(): ?string
    {
        return $this->operator->getSqlOperator(config('database.default'));
    }

    public function transformValue(mixed $value): mixed
    {
        return $this->operator->transformValue($value);
    }

    public function defaultValue(): mixed
    {
        return null;
    }

    public function defaultCallback(): Closure
    {
        return function (Builder $builder, mixed $value) {
            $function = $this->operator->getBuilderFunction();
            $arguments = array_filter([
                $this->key,
                $this->getSqlOperator(),
                $this->transformValue($value),
            ]);

            return $builder->$function(...$arguments);
        };
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'component' => $this->component->value,
            'operators' => $this->getOperators(),
            'props' => $this->componentProps,
            'defaultValue' => $this->defaultValue(),
        ];
    }

    public function toFilterArray(): array
    {
        return [
            'key' => $this->key,
            'operator' => $this->operator->value,
            'value' => $this->currentValue,
        ];
    }
}
