<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolSettingsRequest extends FormRequest
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
            'timezone' => ['required', 'timezone'],
            'allow_online_meetings' => ['required', 'boolean'],
            'allow_translator_requests' => ['required', 'boolean'],
            'booking_buffer_hours' => ['nullable', 'integer', 'min:0'],
            'open_for_contacts_at' => ['nullable', 'date'],
            'close_for_contacts_at' => ['nullable', 'date', 'after:open_for_contacts_at'],
            'open_for_teachers_at' => ['nullable', 'date'],
            'close_for_teachers_at' => ['nullable', 'date', 'after:open_for_teachers_at'],
        ];
    }

    public function attributes()
    {
        return [
            'open_for_contacts_at' => __('opening time'),
            'close_for_contacts_at' => __('closing time'),
            'open_for_teachers_at' => __('opening time'),
            'close_for_teachers_at' => __('closing time'),
        ];
    }

    public function messages()
    {
        return [];
    }
}
