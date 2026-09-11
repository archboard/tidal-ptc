<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property int|null $tenant_id
 * @property int|null $school_id
 * @property-read School|null $school
 * @property-read Tenant|null $tenant
 */
class Activity extends SpatieActivity
{
    use BelongsToSchool;
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            $subject = $activity->subject;
            $activity->tenant_id ??= $subject?->getAttribute('tenant_id') ?? Tenant::current()?->id;
            $activity->school_id ??= $subject?->getAttribute('school_id') ?? School::current()->id;
        });
    }
}
