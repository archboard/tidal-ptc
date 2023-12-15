<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasFirstAndLastName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\HasRolesAndAbilities;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
    use HasFirstAndLastName;
    use HasRolesAndAbilities;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_type' => UserType::class,
    ];

    /**
     * Gets the users who have an ability directly or through a role
     */
    public function scopeWhereCan(Builder $query, string $ability): void
    {
        $query->where(function ($query) use ($ability) {
            // direct
            $query->whereHas('abilities', function ($query) use ($ability) {
                $query->byName($ability);
            });
            // through roles
            $query->orWhereHas('roles', function ($query) use ($ability) {
                $query->whereHas('abilities', function ($query) use ($ability) {
                    $query->byName($ability);
                });
            });
        });
    }

    public function scopeFilter(Builder $builder, array $filters = []): void
    {
        $builder->when($filters['search'] ?? null, function (Builder $builder, string $search) {
            $builder->search($search);
        })->orderBy($filters['sort'] ?? 'last_name', $filters['dir'] ?? 'asc');
    }

    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where(DB::raw("(first_name || ' ' || last_name)"), 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%");
        });
    }

    public function getSchoolPermissionsAttribute(): array
    {
        return $this->getPermissionsForSchool();
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot(['relationship']);
    }

    public function adminSchools(): BelongsToMany
    {
        return $this->schools()
            ->active()
            ->orderBy('name');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getPermissionsForSchool(?School $school = null): array
    {
        $school = $school ?? $this->school;

        return [
            [
                'label' => __('Change school settings'),
                'permission' => 'change settings',
                'selected' => $this->can('change settings', $school),
            ],
        ];
    }

    public function syncFromSis(): static
    {
        $this->tenant->getSisProvider()
            ->syncUser($this);

        return $this;
    }
}
