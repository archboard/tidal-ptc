<?php

namespace App\Models;

use App\Enums\NotificationEvent;
use App\Enums\Role;
use App\Enums\UserType;
use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasFirstAndLastName;
use App\Traits\HasHiddenAttribute;
use App\Traits\HasPermissions;
use App\Traits\HasTimezone;
use App\Traits\Selectable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Silber\Bouncer\Database\HasRolesAndAbilities;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
    use HasFirstAndLastName;
    use HasHiddenAttribute;
    use HasPermissions;
    use HasRolesAndAbilities;
    use HasTimezone;
    use Notifiable;
    use Selectable;

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
        'is_24h' => 'boolean',
        'hidden' => 'boolean',
        'notification_config' => 'collection',
    ];

    //-------------------------------------------------------------------------
    // Query scopes
    //-------------------------------------------------------------------------

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

    //-------------------------------------------------------------------------
    // Relationships
    //-------------------------------------------------------------------------

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

    public function selectedModels(): HasMany
    {
        return $this->hasMany(SelectedModel::class);
    }

    //-------------------------------------------------------------------------
    // Instance functions
    //-------------------------------------------------------------------------

    public function syncFromSis(): static
    {
        $this->tenant->getSisProvider()
            ->syncUser($this);

        return $this;
    }

    public function getNotificationOptions(): Collection
    {
        return NotificationEvent::collect()
            ->filter(fn (NotificationEvent $event) => in_array($this->user_type, $event->getUserTypes()));
    }

    public function assignRole(Role|string $role): static
    {
        return $this->assign($role?->value ?? $role);
    }

    /**
     * Toggles a model as selected for the user.
     */
    public function toggleSelectedModelInstance(Model $model): static
    {
        $selection = $this->selectedModels()
            ->firstOrCreate([
                'tenant_id' => $this->tenant_id,
                'school_id' => $this->school_id,
                'user_id' => $this->id,
                'selectable_type' => $model->getMorphClass(),
                'selectable_id' => $model->getKey(),
            ]);

        if (! $selection->wasRecentlyCreated) {
            $selection->delete();
        }

        return $this;
    }

    public function toggleSelectedModel(string $modelAlias, int $id): static
    {
        if (class_exists($modelAlias)) {
            $modelAlias = (new $modelAlias)->getMorphClass();
        }

        if ($model = Relation::getMorphedModel($modelAlias)) {
            return $this->toggleSelectedModelInstance(new $model(['id' => $id]));
        }

        return $this;
    }

    public function selectAllModel(string $modelAlias, array $filters = []): static
    {
        if (class_exists($modelAlias)) {
            $modelAlias = (new $modelAlias)->getMorphClass();
        }

        if (Relation::getMorphedModel($modelAlias)) {
            $relationship = Str::plural($modelAlias);

            $data = $this->school
                ->$relationship()
                ->filter($filters)
                ->pluck('id')
                ->map(fn ($id) => [
                    'tenant_id' => $this->tenant_id,
                    'school_id' => $this->school_id,
                    'user_id' => $this->id,
                    'selectable_type' => $modelAlias,
                    'selectable_id' => $id,
                ]);

            $this->deselectAllModel($modelAlias)
                ->selectedModels()
                ->insert($data->toArray());
        }

        return $this;
    }

    public function deselectAllModel(string $modelAlias): static
    {
        $this->selectedModels()
            ->where('selectable_type', $modelAlias)
            ->where('school_id', $this->school_id)
            ->delete();

        return $this;
    }

    public function getModelSelection(string $model): Collection
    {
        $modelAlias = class_exists($model)
            ? (new $model())->getMorphClass()
            : $model;

        return $this->selectedModels()
            ->where('school_id', $this->school_id)
            ->where('selectable_type', $modelAlias)
            ->pluck('selectable_id')
            ->values();
    }
}
