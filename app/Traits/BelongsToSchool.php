<?php

namespace App\Traits;

use App\Models\School;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
