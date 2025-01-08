<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\TimeSlot;
use App\Models\User;

class TimeSlotPolicy
{
    public function createOrForSelf(User $user): bool
    {
        return $user->can(Permission::create, TimeSlot::class)
            || ($user->school->teachers_can_create && $user->canOwnTimeSlots());
    }

    public function updateOrForSelf(User $user, TimeSlot $timeSlot): bool
    {
        return $user->id === $timeSlot->user_id
            || $user->can(Permission::update, $timeSlot);
    }

    public function deleteOrForSelf(User $user, TimeSlot $timeSlot): bool
    {
        return $user->id === $timeSlot->user_id
            || $user->can(Permission::delete, $timeSlot);
    }
}
