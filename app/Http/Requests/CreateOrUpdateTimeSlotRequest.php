<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrUpdateTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $forSelf = $this->input('user_id') &&
            is_null($this->input('batch_id')) &&
            $user->id === $this->input('user_id') &&
            $this->school()->teachers_can_create;

        if ($this->isMethod('post')) {
            return $user->can(Permission::create, TimeSlot::class) || $forSelf;
        }

        return $user->can(Permission::update, TimeSlot::class) || $forSelf;
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
            'user_id' => ['nullable', 'integer'],
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

    public function getTimeSlotAttributes(bool $shiftTime = true): array
    {
        $school = $this->school();
        $validated = $this->validated();

        if (! ($validated['user_id'] ?? false)) {
            $validated['user_id'] = $this->user()->id;
        }

        return [
            ...$validated,
            'starts_at' => $shiftTime
                ? $school->dateToApp($validated['starts_at'])->toDateTimeString()
                : $validated['starts_at'],
            'ends_at' => $shiftTime
                ? $school->dateToApp($validated['ends_at'])->toDateTimeString()
                : $validated['ends_at'],
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
