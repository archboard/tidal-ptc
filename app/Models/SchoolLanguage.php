<?php

namespace App\Models;

use App\Enums\Language;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Language $language
 * @property-read \App\Models\School|null $school
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage query()
 *
 * @mixin \Eloquent
 */
class SchoolLanguage extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'request_max' => 'integer',
            'overlap_max' => 'integer',
            'language' => Language::class,
        ];
    }
}
