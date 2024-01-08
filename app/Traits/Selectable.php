<?php

namespace App\Traits;

use App\Models\SelectedModel;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Selectable
{
    public function selectedModel(): MorphOne
    {
        return $this->morphOne(SelectedModel::class, 'selectable');
    }
}
