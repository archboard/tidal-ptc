<?php

namespace App\Traits;

use Carbon\CarbonImmutable;
use Carbon\FactoryImmutable as Factory;

trait HasTimezone
{
    public function getCarbonSettings(): array
    {
        return [
            'locale' => app()->getLocale(),
            'timezone' => $this->timezone ?? config('app.timezone'),
        ];
    }

    public function getCarbonFactory(): Factory
    {
        return new Factory($this->getCarbonSettings());
    }

    public function now(): CarbonImmutable
    {
        return $this->getCarbonFactory()
            ->now($this->getCarbonSettings()['timezone']);
    }

    public function today(): CarbonImmutable
    {
        return $this->getCarbonFactory()
            ->today();
    }

    public function dateToApp(string|CarbonImmutable $date): CarbonImmutable
    {
        return $this->getCarbonFactory()
            ->parse($date)
            ->setTimezone(config('app.timezone'));
    }

    public function dateFromApp(string|CarbonImmutable $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)
            ->setTimezone($this->timezone ?? config('app.timezone'))
            ->settings($this->getCarbonSettings());
    }
}
