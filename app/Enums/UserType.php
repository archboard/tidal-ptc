<?php

namespace App\Enums;

use App\Enums\Traits\Collectable;
use App\Enums\Traits\HasOptions;
use App\Exceptions\UnknownPersonaException;
use App\Models\Tenant;
use Illuminate\Support\Collection;

enum UserType: string
{
    use HasOptions;
    use Collectable;

    case staff = 'staff';
    case guardian = 'guardian';
    case student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::staff => __('Staff'),
            self::guardian => __('Contact'),
            self::student => __('Student'),
        };
    }

    public static function fromData(Collection $data): UserType
    {
        /** @var string|null $persona */
        $persona = $data->get('persona', $data->get('usertype'));

        return match ($persona) {
            'student' => self::student,
            'staff', 'teacher' => self::staff,
            'guardian', 'parent' => self::guardian,
            default => throw new UnknownPersonaException,
        };
    }

    public function getSisKeyFromData(Collection $data): string
    {
        $tenant = Tenant::current();
        $id = $data->get('usersDCID') ??
            $data->get('ps_dcid') ??
            $data->get('dcid');

        return "{$tenant->id}|{$this->value}|{$id}";
    }

    public function getSisKeyFromSisId(string|int $sisId): string
    {
        $tenant = Tenant::current();

        return "{$tenant->id}|{$this->value}|{$sisId}";
    }
}
