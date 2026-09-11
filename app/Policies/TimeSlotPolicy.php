<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\User;

class TimeSlotPolicy
{
    public function createOrForSelf(User $user): bool
    {
        return $user->can(Permission::create, TimeSlot::class)
            || ($user->school?->teachers_can_create && $user->canOwnTimeSlots());
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

    public function reserve(User $user, TimeSlot $timeSlot, Student $student): bool
    {
        if ($user->can(Permission::update, $timeSlot)) {
            return true;
        }

        return $timeSlot->contact_can_book
            && $timeSlot->school->contacts_can_book
            && $user->students()->whereKey($student->id)->exists();
    }

    public function cancelReservation(User $user, TimeSlot $timeSlot): bool
    {
        return $user->id === $timeSlot->reserved_by
            || $user->id === $timeSlot->user_id
            || $user->can(Permission::update, $timeSlot);
    }

    public function viewReservation(User $user, TimeSlot $timeSlot): bool
    {
        return $this->cancelReservation($user, $timeSlot);
    }
}
