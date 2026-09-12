<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Enums\Permission;
use App\Models\TimeSlot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::update, TimeSlot::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['boolean'],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => [Rule::enum(Language::class), Rule::in($this->school()->languages->pluck('language')->map->value)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'languages.*.in' => __('That language is not offered by the school.'),
        ];
    }
}
