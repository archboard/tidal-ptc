<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SelectedModel extends Model
{
    use BelongsToTenant;
    use BelongsToSchool;
    use BelongsToUser;

    protected $guarded = [];
    public $timestamps = false;

    public function selectable(): MorphTo
    {
        return $this->morphTo();
    }
}
