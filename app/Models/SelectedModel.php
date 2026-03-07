<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperSelectedModel
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int $user_id
 * @property string $selectable_type
 * @property int $selectable_id
 * @property-read \App\Models\School $school
 * @property-read Model|\Eloquent $selectable
 * @property-read \App\Models\Tenant $tenant
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereSchoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereSelectableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereSelectableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelectedModel whereUserId($value)
 *
 * @mixin \Eloquent
 */
class SelectedModel extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;

    protected $guarded = [];

    public $timestamps = false;

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function selectable(): MorphTo
    {
        return $this->morphTo();
    }
}
