<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Enums\Permission;
use App\Models\SchoolLanguage;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a reservation of a time slot. On store the slot is the route model and the student
 * comes from the body; on reschedule (update) the route model is the source reservation, the
 * target slot comes from `time_slot_id`, and the student is copied from the source.
 */
class ReserveTimeSlotRequest extends FormRequest
{
    protected ?TimeSlot $target = null;

    public function authorize(): bool
    {
        $student = $this->student();

        if (! $student) {
            return true;
        }

        return $this->user()?->can('reserve', [$this->target(), $student]) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requested_online' => $this->target()->allow_online_meetings && $this->boolean('requested_online'),
        ]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $school = $this->school();
        $translatorsAllowed = $school->allow_translator_requests && $this->target()->allow_translator_requests;

        return [
            'time_slot_id' => [
                Rule::requiredIf($this->isRescheduling()),
                Rule::exists('time_slots', 'id')->where('school_id', $school->id),
            ],
            'student_id' => [
                Rule::requiredIf(! $this->isRescheduling()),
                Rule::exists('students', 'id')->where('school_id', $school->id),
            ],
            'contact_notes' => ['nullable', 'string', 'max:1000'],
            'requested_online' => ['boolean'],
            'language' => [
                'nullable',
                $translatorsAllowed ? Rule::enum(Language::class) : 'prohibited',
                Rule::in($school->languages->pluck('language')->map->value),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'language.prohibited' => __('Translator requests are not available for this time slot.'),
            'language.in' => __('That language is not offered by the school.'),
        ];
    }

    /** @return array<int, Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $slot = $this->target();
                $student = $this->student();
                /** @var User $user */
                $user = $this->user();

                if (! $student) {
                    return;
                }

                if ($slot->isReserved()) {
                    $validator->errors()->add('time_slot_id', __('This time slot has already been reserved.'));
                }

                if (! $user->can(Permission::update, $slot) && $slot->starts_at->lte(now()->addHours($this->school()->booking_buffer_hours))) {
                    $validator->errors()->add('time_slot_id', __('This time slot can no longer be booked.'));
                }

                if (! $student->can_book || ! $student->canMeetWith($slot->user)) {
                    $validator->errors()->add('student_id', __('This student cannot book a conference with this staff member.'));
                }

                $upcoming = $student->timeSlots()
                    ->notExpired()
                    ->whereKeyNot($this->source()?->id);

                if ((clone $upcoming)->where('user_id', $slot->user_id)->exists()) {
                    $validator->errors()->add('student_id', __('This student already has an upcoming conference with this staff member.'));
                }

                $overlapsStudent = (clone $upcoming)
                    ->whereOverlaps($slot->starts_at->toDateTimeString(), $slot->ends_at->toDateTimeString())
                    ->exists();
                $overlapsContact = $user->bookedTimeSlots()
                    ->notExpired()
                    ->whereKeyNot($this->source()?->id)
                    ->whereOverlaps($slot->starts_at->toDateTimeString(), $slot->ends_at->toDateTimeString())
                    ->exists();

                if ($overlapsStudent || $overlapsContact) {
                    $validator->errors()->add('time_slot_id', __('This time slot overlaps with another reservation.'));
                }

                if ($this->input('language')) {
                    $this->validateTranslatorCapacity($validator, $slot);
                }
            },
        ];
    }

    protected function validateTranslatorCapacity(Validator $validator, TimeSlot $slot): void
    {
        /** @var SchoolLanguage|null $language */
        $language = $this->school()->languages->firstWhere('language', Language::from($this->input('language')));

        if (! $language) {
            return;
        }

        $requests = TimeSlot::query()
            ->where('school_id', $slot->school_id)
            ->where('language', $language->language)
            ->reserved()
            ->notExpired()
            ->whereKeyNot($this->source()?->id);

        if ($language->request_max > 0 && (clone $requests)->count() >= $language->request_max) {
            $validator->errors()->add('language', __('No more translator requests are available for this language.'));
        }

        $overlapping = (clone $requests)
            ->whereOverlaps($slot->starts_at->toDateTimeString(), $slot->ends_at->toDateTimeString())
            ->count();

        if ($language->overlap_max > 0 && $overlapping >= $language->overlap_max) {
            $validator->errors()->add('language', __('No translator is available for this language at that time.'));
        }
    }

    public function isRescheduling(): bool
    {
        return $this->isMethod('put') || $this->isMethod('patch');
    }

    /** The reservation being moved, when rescheduling. */
    public function source(): ?TimeSlot
    {
        return $this->isRescheduling() ? $this->routeSlot() : null;
    }

    protected function routeSlot(): TimeSlot
    {
        /** @var TimeSlot $timeSlot */
        $timeSlot = $this->route('time_slot');

        return $timeSlot;
    }

    /** The slot being reserved. */
    public function target(): TimeSlot
    {
        if ($this->target) {
            return $this->target;
        }

        /** @var TimeSlot $target */
        $target = $this->isRescheduling()
            ? TimeSlot::findOrFail((int) $this->input('time_slot_id'))
            : $this->routeSlot();

        return $this->target = $target;
    }

    public function student(): ?Student
    {
        if ($source = $this->source()) {
            return $source->student;
        }

        /** @var Student|null $student */
        $student = Student::find($this->input('student_id'));

        return $student;
    }

    /** @return array<string, mixed> */
    public function reservationAttributes(): array
    {
        /** @var User $user */
        $user = $this->user();
        $source = $this->source();

        return [
            'student_id' => $this->student()?->id,
            'reserved_by' => $source ? $source->reserved_by : $user->id,
            'reserved_at' => $source ? $source->reserved_at : now(),
            'contact_notes' => $this->input('contact_notes'),
            'requested_online' => $this->boolean('requested_online'),
            'language' => $this->input('language'),
        ];
    }
}
