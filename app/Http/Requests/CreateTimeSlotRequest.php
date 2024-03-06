<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class CreateTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batch_id' => ['nullable'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'teacher_notes' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:255', 'url'],
            'is_online' => ['nullable', 'boolean'],
            'contact_can_book' => ['nullable', 'boolean'],
            'allow_translator_requests' => ['nullable', 'boolean'],
            'allow_online_meetings' => ['nullable', 'boolean'],
        ];
    }

    public function getTimeSlotAttributes(): array
    {
        $school = $this->school();
        $validated = $this->validated();

        return [
            ...$validated,
            'starts_at' => CarbonImmutable::parse($validated['starts_at'], $school->timezone)
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString(),
            'ends_at' => CarbonImmutable::parse($validated['ends_at'], $school->timezone)
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString(),
            'is_online' => $school->allow_online_meetings
                ? $validated['is_online']
                : false,
            'allow_online_meetings' => $school->allow_online_meetings
                ? $validated['allow_online_meetings']
                : false,
            'tenant_id' => $school->tenant_id,
            'school_id' => $school->id,
            'created_by' => $this->user()->id,
        ];
    }
}
