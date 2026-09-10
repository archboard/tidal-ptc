<?php

namespace App\Models;

use App\Enums\Language;
use App\Traits\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Language $language
 * @property-read School|null $school
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage query()
 *
 * @property int $id
 * @property int $school_id
 * @property int $request_max
 * @property int $overlap_max
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereOverlapMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereRequestMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereSchoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SchoolLanguage whereUpdatedAt($value)
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
