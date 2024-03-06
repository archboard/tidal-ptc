<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use BelongsToTenant;
    use BelongsToSchool;
    use BelongsToUser;

    protected $guarded = [];

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }
}
