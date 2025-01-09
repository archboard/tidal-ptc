<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $timeSlot = $this->route('time_slot');

        if ($this->input('batch_id') && $this->boolean('update_batch')) {
            return $this->user()->can(Permission::update, $timeSlot);
        }

        return $this->user()->can('updateOrForSelf', $timeSlot);
    }

    protected function prepareForValidation(): void
    {
        $school = $this->school();

        $this->merge([
            'is_online' => $school->allow_online_meetings &&
                $this->boolean('is_online'),
            'allow_online_meetings' => $school->allow_online_meetings &&
                $this->boolean('allow_online_meetings'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batch_id' => ['nullable', Rule::exists('batches', 'id')],
            'teacher_notes' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => [
                'nullable',
                'string',
                'max:255',
                'url',
                Rule::requiredIf($this->boolean('is_online') && ! $this->user()->meeting_url),
            ],
            'is_online' => ['nullable', 'boolean'],
            'contact_can_book' => ['nullable', 'boolean'],
            'allow_translator_requests' => ['nullable', 'boolean'],
            'allow_online_meetings' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'meeting_url.required_if' => __('A meeting URL is required if the time slot is online and you do not have a default meeting URL in your personal settings.'),
        ];
    }
}
