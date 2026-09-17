<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\NotificationEvent;
use App\Enums\Permission;
use App\Http\Requests\ReserveTimeSlotRequest;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\StudentResource;
use App\Http\Resources\TimeSlotResource;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

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
        'translator_id' => null,
        'contact_reminded_at' => null,
        'staff_reminded_at' => null,
    ];

    /** Booking page for one student with one staff member; JSON for the dashboard modal. */
    public function create(Request $request, Student $student, User $user): Response|JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $school = $request->school();

        abort_unless(
            $actor->can(Permission::update, TimeSlot::class) || $actor->students()->whereKey($student->id)->exists(),
            403
        );
        abort_unless($student->canMeetWith($user), 403, __('This student cannot book a conference with this staff member.'));

        $existing = $student->timeSlots()->notExpired()->where('user_id', $user->id)->first();
        $slots = TimeSlot::query()
            ->when(
                $actor->can(Permission::update, TimeSlot::class),
                fn ($query) => $query->notReserved()->where('school_id', $school->id)->notExpired(),
                fn ($query) => $query->bookable($school),
            )
            ->where('user_id', $user->id)
            ->orderBy('starts_at')
            ->get();

        $props = [
            'student' => new StudentResource($student),
            'staff' => new PublicUserResource($user),
            'slots' => TimeSlotResource::collection($slots),
            'existingReservation' => $existing ? new TimeSlotResource($existing) : null,
            'languages' => $school->allow_translator_requests
                ? $school->languages->map(fn ($language) => ['value' => $language->language->value, 'label' => $language->language->name()])->values()
                : [],
            'allowOnline' => $school->allow_online_meetings,
        ];

        if ($request->wantsJson() && ! $request->inertia()) {
            return response()->json($props);
        }

        return inertia('reservations/Create', ['title' => __('Book a conference'), ...$props]);
    }

    public function store(ReserveTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $timeSlot) {
            $this->claim($timeSlot, $request->reservationAttributes());
        });

        $timeSlot->refresh()->notifyReservation(NotificationEvent::slot_booked);
        $this->logReservation(ActivityEvent::reservation_booked, $timeSlot);

        return $this->toDashboard($request, __('Conference booked successfully.'));
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

        return $this->toDashboard($request, __('Conference rescheduled successfully.'));
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

    /** Inertia bookings come from the booking page; send them home rather than back to it. */
    protected function toDashboard(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->inertia()) {
            session()->flash('success', $message);

            return to_route('home');
        }

        return $this->toSuccess($request, $message);
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
