<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperLanguage
 */
class Language extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}
