<?php

namespace App\Models;

use App\Enums\NotificationEvent;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserType;
use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasFirstAndLastName;
use App\Traits\HasHiddenAttribute;
use App\Traits\HasPermissions;
use App\Traits\HasTimeSlots;
use App\Traits\HasTimezone;
use App\Traits\Selectable;
use Closure;
use GrantHolle\ModelFilters\Filters\MultipleSelectFilter;
use GrantHolle\ModelFilters\Filters\TextFilter;
use GrantHolle\ModelFilters\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $sis_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $password
 * @property int|null $school_id
 * @property string|null $timezone
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string $sis_key
 * @property UserType|null $user_type
 * @property string $locale
 * @property bool $is_24h
 * @property \Illuminate\Support\Collection<array-key, mixed>|null $notification_config
 * @property bool $can_book
 * @property string|null $meeting_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Silber\Bouncer\Database\Ability> $abilities
 * @property-read int|null $abilities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\School> $adminSchools
 * @property-read int|null $admin_schools_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Section> $altSections
 * @property-read int|null $alt_sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeSlot> $bookedTimeSlots
 * @property-read int|null $booked_time_slots_count
 * @property-read array $full_calendar_format
 * @property-read mixed $last_first
 * @property-read mixed $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read mixed $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Silber\Bouncer\Database\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\School|null $school
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\School> $schools
 * @property-read int|null $schools_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Section> $sections
 * @property-read int|null $sections_count
 * @property-read \App\Models\SelectedModel|null $selectedModel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SelectedModel> $selectedModels
 * @property-read int|null $selected_models_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students
 * @property-read int|null $students_count
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 *
 * @method static Builder<static>|User canBook()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User filter(\Illuminate\Support\Collection|array $data)
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User search(string|int $search)
 * @method static Builder<static>|User whereCan(string $ability)
 * @method static Builder<static>|User whereCanBook($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereFirstName($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIs($role)
 * @method static Builder<static>|User whereIs24h($value)
 * @method static Builder<static>|User whereIsAll($role)
 * @method static Builder<static>|User whereIsNot($role)
 * @method static Builder<static>|User whereLastName($value)
 * @method static Builder<static>|User whereLocale($value)
 * @method static Builder<static>|User whereMeetingUrl($value)
 * @method static Builder<static>|User whereNotificationConfig($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereSchoolId($value)
 * @method static Builder<static>|User whereSisId($value)
 * @method static Builder<static>|User whereSisKey($value)
 * @method static Builder<static>|User whereTenantId($value)
 * @method static Builder<static>|User whereTimezone($value)
 * @method static Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder<static>|User whereTwoFactorSecret($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User whereUserType($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
    use HasFilters;
    use HasFirstAndLastName;
    use HasHiddenAttribute;
    use HasPermissions;
    use HasRolesAndAbilities;
    use HasTimeSlots;
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
        'can_book' => 'boolean',
        'notification_config' => 'collection',
    ];

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

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

    public function scopeSearch(Builder $builder, string|int $search): void
    {
        $builder->when(is_numeric($search), function (Builder $builder) use ($search) {
            $builder->where('id', $search);
        })->unless(is_numeric($search), function (Builder $builder) use ($search) {
            $builder->where(function (Builder $builder) use ($search) {
                $builder->where(DB::raw("(first_name || ' ' || last_name)"), 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

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

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function altSections(): HasMany
    {
        return $this->hasMany(Section::class, 'alt_user_id');
    }

    public function bookedTimeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'reserved_by');
    }

    // -------------------------------------------------------------------------
    // Custom accessors and mutators
    // -------------------------------------------------------------------------

    public function fullCalendarFormat(): Attribute
    {
        return Attribute::get(function (): array {
            return [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'omitZeroMinute' => false,
                'meridiem' => $this->is_24h ? false : 'short',
                'hour12' => ! $this->is_24h,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Instance functions
    // -------------------------------------------------------------------------

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

    public function selectAllModel(string $modelAlias, array|Collection $filters = []): static
    {
        if ($alias = Str::toModelAlias($modelAlias)) {
            $relationship = Str::plural($alias);

            $data = $this->school
                ->$relationship()
                ->filter($filters)
                ->pluck('id')
                ->map(fn ($id) => [
                    'tenant_id' => $this->tenant_id,
                    'school_id' => $this->school_id,
                    'user_id' => $this->id,
                    'selectable_type' => $alias,
                    'selectable_id' => $id,
                ]);

            $this->deselectAllModel($alias)
                ->selectedModels()
                ->insert($data->toArray());
        }

        return $this;
    }

    public function deselectAllModel(string $modelAlias): static
    {
        $this->selectedModels()
            ->where('selectable_type', Str::toModelAlias($modelAlias))
            ->where('school_id', $this->school_id)
            ->delete();

        return $this;
    }

    public function getModelSelection(string $model, ?Closure $where = null): Collection
    {
        return $this->selectedModels()
            ->where('school_id', $this->school_id)
            ->where('selectable_type', Str::toModelAlias($model))
            ->when($where, $where)
            ->pluck('selectable_id')
            ->values();
    }

    public function updateModelSelectionAttributes(string $model, array $data): static
    {
        $modelClass = Str::toModelClass($model);

        $modelClass::query()
            ->whereIn('id', function ($builder) use ($model) {
                $builder->select('selectable_id')
                    ->from('selected_models')
                    ->where('user_id', $this->id)
                    ->where('school_id', $this->school_id)
                    ->where('selectable_type', Str::toModelAlias($model));
            })
            ->update($data);

        return $this;
    }

    public function filters(): array
    {
        return [
            TextFilter::make('search', __('Search'))
                ->hide()
                ->using(fn (Builder $builder, string $search) => $builder->search($search)),
            TextFilter::make('first_name', __('First name')),
            TextFilter::make('last_name', __('Last name')),
            MultipleSelectFilter::make('user_type', __('User type'))
                ->options(UserType::options()),
            MultipleSelectFilter::make('teacher', __('Teachers'))
                ->hide()
                ->using(fn (Builder $builder) => $builder
                    ->whereHas('sections')
                    ->orWhereHas('altSections')),
        ];
    }

    public function fullCalendarEventUrl(): string
    {
        return route('users.event-source', $this);
    }

    public function getFullCalendarEventSources(): array
    {
        // Get all the students' event sources
        return $this->students->map(fn (Student $student) => $student->fullCalendarEventSource())
            ->push($this->fullCalendarEventSource())
            ->toArray();
    }

    public function createBatchFromSelection(string $selectionType = User::class): Batch
    {
        $batch = Batch::create([
            'tenant_id' => $this->tenant_id,
            'school_id' => $this->school_id,
            'user_id' => $this->id,
        ]);

        return $this->associateSelectionWithBatch($batch, $selectionType);
    }

    public function associateSelectionWithBatch(Batch $batch, string $selectionType = User::class): Batch
    {
        $batchUsers = $this->getModelSelection($selectionType)
            ->map(fn ($id) => [
                'batch_id' => $batch->id,
                'user_id' => $id,
            ]);

        DB::table('batch_users')->insert($batchUsers->toArray());

        return $batch;
    }

    public function canOwnTimeSlots(): bool
    {
        return $this->can(Permission::ownTimeSlots) ||
            $this->sections()->exists();
    }
}
