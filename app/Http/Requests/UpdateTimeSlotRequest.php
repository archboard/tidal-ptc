<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimeSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();
        $timeSlot = $this->route('time_slot');

        if ($this->updateBatch()) {
            return $user->can(Permission::update, $timeSlot);
        }

        return $user->can('updateOrForSelf', $timeSlot);
    }

    protected function prepareForValidation(): void
    {
        $school = $this->school();
        /** @var User $user */
        $user = $this->user();
        $starts = $this->input('starts_at');
        $ends = $this->input('ends_at');

        $this->merge([
            'is_online' => $school->allow_online_meetings &&
                $this->boolean('is_online'),
            'allow_online_meetings' => $school->allow_online_meetings &&
                $this->boolean('allow_online_meetings'),
            'starts_at' => $this->dateInIsoFormat($starts)
                ? $user->dateToApp($starts)->toDateTimeString()
                : $starts,
            'ends_at' => $this->dateInIsoFormat($ends)
                ? $user->dateToApp($ends)->toDateTimeString()
                : $ends,
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
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where('school_id', $this->school()->id)],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'teacher_notes' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => [
                'nullable',
                'string',
                'max:255',
                'url',
                Rule::requiredIf($this->boolean('is_online') && ! $this->user()?->meeting_url),
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

    public function updateBatch(): bool
    {
        return $this->input('batch_id') && $this->boolean('update_batch');
    }

    protected function dateInIsoFormat(string $date): bool
    {
        return CarbonImmutable::hasFormat($date, 'Y-m-d\TH:i:sP');
    }
}
