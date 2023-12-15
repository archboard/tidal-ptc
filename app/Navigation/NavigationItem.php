<?php

namespace App\Navigation;

class NavigationItem
{
    public string $component = 'InertiaLink';

    public string $method = 'get';

    public string $target = '_self';

    public string $label = '';

    public string $endpoint = '';

    public string $as = 'a';

    public string $icon = '';

    public bool $current = false;

    public static function make(): static
    {
        return new static;
    }

    public function useComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function inNewTab(): static
    {
        $this->target = '_blank';

        return $this;
    }

    public function method(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function labeled(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function to(string $url): static
    {
        $this->endpoint = $url;

        return $this;
    }

    public function asButton(): static
    {
        $this->as = 'button';

        return $this;
    }

    public function withIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function isCurrent(bool $current): static
    {
        $this->current = $current;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->endpoint,
            'label' => $this->label,
            'method' => $this->method,
            'target' => $this->target,
            'as' => $this->as,
            'component' => $this->component,
            'icon' => $this->icon,
            'current' => $this->current,
        ];
    }
}
