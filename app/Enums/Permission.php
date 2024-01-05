<?php

namespace App\Enums;

use App\Enums\Traits\HasOptions;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

enum Permission: string
{
    use HasOptions;

    case everything = '*';
    case viewAny = 'viewAny';
    case view = 'view';
    case create = 'create';
    case update = 'update';
    case delete = 'delete';
    case editTenantSettings = 'edit tenant settings';
    case editSchoolSettings = 'edit school settings';
    case editPermissions = 'edit permissions';

    public function label(): string
    {
        return match ($this) {
            self::viewAny => __('View list'),
            self::view => __('View'),
            self::create => __('Create'),
            self::update => __('Update'),
            self::delete => __('Delete'),
            self::editTenantSettings => __('Edit tenant settings'),
            self::editSchoolSettings => __('Edit school settings'),
            self::everything => __('Manages tenancy'),
            self::editPermissions => __('Edit user permissions'),
        };
    }

    public function description(): ?string
    {
        return match ($this) {
            self::viewAny => __('View resource listing'),
            self::view => __('View individual resource details'),
            self::everything => __('Gives the user full access to the entire tenancy with full permissions for everything.'),
            self::editSchoolSettings => __('Allows the user to edit all school settings.'),
            self::editTenantSettings => __('Allows the user to edit all tenant settings.'),
            default => null,
        };
    }

    public function key(): string
    {
        return Str::of($this->value)
            ->replace(' ', '_');
    }

    public static function fromKey(string $key): Permission
    {
        $value = Str::of($key)
            ->replace('_', ' ');

        return self::from($value);
    }

    public function isCrud(): bool
    {
        return match ($this) {
            self::viewAny, self::view, self::create, self::update, self::delete => true,
            default => false,
        };
    }

    public function isSisCrud(): bool
    {
        return match ($this) {
            self::viewAny, self::view, self::update => true,
            default => false,
        };
    }

    public static function getCrud(): Collection
    {
        return self::collect()
            ->filter(fn (Permission $permission) => $permission->isCrud());
    }

    public static function getNonCrud(): Collection
    {
        return self::collect()
            ->filter(fn (Permission $permission) => ! $permission->isCrud());
    }

    public function shouldBeScoped(): bool
    {
        return match ($this) {
            self::everything, self::editTenantSettings => false,
            default => true,
        };
    }

    public function toEntry(User $user, ?string $className = null): array
    {
        return [
            'key' => $this->key(),
            'value' => $this->value,
            'label' => $this->label(),
            'description' => $this->description(),
            'granted' => $user->can($this->value, $className),
        ];
    }

    public function toMiddleware(): string
    {
        return 'can:'.$this->value;
    }
}
