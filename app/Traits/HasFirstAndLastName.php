<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasFirstAndLastName
{
    public function name(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    public function lastFirst(): Attribute
    {
        return Attribute::get(fn () => "{$this->last_name}, {$this->first_name}");
    }
}
