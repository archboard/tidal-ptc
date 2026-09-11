<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\NotificationEvent;
use App\Enums\Permission;
use App\Http\Requests\ReserveTimeSlotRequest;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /** @var array<string, mixed> */
    protected const array EMPTY_RESERVATION = [
        'student_id' => null,
        'reserved_by' => null,
        'reserved_at' => null,
        'contact_notes' => null,
        'requested_online' => false,
        'language' => null,
        'translator_notes' => null,
        'contact_reminded_at' => null,
        'staff_reminded_at' => null,
    ];

    public function store(ReserveTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $timeSlot) {
            $this->claim($timeSlot, $request->reservationAttributes());
        });

        $timeSlot->refresh()->notifyReservation(NotificationEvent::slot_booked);
        $this->logReservation(ActivityEvent::reservation_booked, $timeSlot);

        return $this->toSuccess($request, __('Conference booked successfully.'));
    }

    /**
     * Reschedule: move the reservation on the route slot to the slot given by `time_slot_id`.
     */
    public function update(ReserveTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse|RedirectResponse
    {
        $this->authorize('cancelReservation', $timeSlot);
        $target = $request->target();
        abort_unless($target->user_id === $timeSlot->user_id, 422, __('Reservations can only be moved to the same staff member.'));

        DB::transaction(function () use ($request, $timeSlot, $target) {
            $this->claim($target, [
                ...$request->reservationAttributes(),
                'translator_notes' => $timeSlot->translator_notes,
            ]);
            $timeSlot->update(self::EMPTY_RESERVATION);
        });

        $target->refresh()->notifyReservation(NotificationEvent::slot_rescheduled, previous: $timeSlot);
        $this->logReservation(ActivityEvent::reservation_rescheduled, $target, [
            'from_time_slot_id' => $timeSlot->id,
            'from_starts_at' => $timeSlot->starts_at->toDateTimeString(),
            'from_ends_at' => $timeSlot->ends_at->toDateTimeString(),
        ]);

        return $this->toSuccess($request, __('Conference rescheduled successfully.'));
    }

    public function destroy(Request $request, TimeSlot $timeSlot): JsonResponse|RedirectResponse
    {
        $this->authorize('cancelReservation', $timeSlot);
        /** @var User $user */
        $user = $request->user();

        abort_if(
            $user->id === $timeSlot->reserved_by
                && $user->id !== $timeSlot->user_id
                && ! $user->can(Permission::update, $timeSlot)
                && $timeSlot->starts_at->lte(now()->addHours($request->school()->booking_buffer_hours)),
            403,
            __('This reservation can no longer be cancelled online. Please contact the school.')
        );

        $timeSlot->notifyReservation(NotificationEvent::slot_cancelled);
        $this->logReservation(ActivityEvent::reservation_cancelled, $timeSlot);
        $timeSlot->update(self::EMPTY_RESERVATION);

        return $this->toSuccess($request, __('Conference cancelled.'));
    }

    /**
     * Lock the slot row and fill it, so two concurrent bookings cannot both succeed.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function claim(TimeSlot $timeSlot, array $attributes): void
    {
        /** @var TimeSlot $locked */
        $locked = TimeSlot::query()->lockForUpdate()->findOrFail($timeSlot->id);
        abort_if($locked->isReserved(), 409, __('This time slot has already been reserved.'));

        $locked->update($attributes);
    }

    /** @param array<string, mixed> $extra */
    protected function logReservation(ActivityEvent $event, TimeSlot $timeSlot, array $extra = []): void
    {
        $event->log($timeSlot, [
            'student_id' => $timeSlot->student_id,
            'student' => $timeSlot->student?->name,
            'contact_id' => $timeSlot->reserved_by,
            'contact' => $timeSlot->reservedBy?->name,
            'starts_at' => $timeSlot->starts_at->toDateTimeString(),
            'ends_at' => $timeSlot->ends_at->toDateTimeString(),
            'language' => $timeSlot->language?->value,
            'requested_online' => $timeSlot->requested_online,
            ...$extra,
        ]);
    }
}
