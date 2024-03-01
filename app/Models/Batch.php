<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use BelongsToTenant;
    use BelongsToSchool;
    use BelongsToUser;

    protected $guarded = [];
}
