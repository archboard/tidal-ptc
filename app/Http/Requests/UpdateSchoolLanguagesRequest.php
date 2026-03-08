<?php

namespace App\Http\Requests;

use App\Enums\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolLanguagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'languages' => ['array'],
            'languages.*.code' => ['required', Rule::enum(Language::class)],
            'languages.*.request_max' => ['nullable', 'integer', 'min:0'],
            'languages.*.overlap_max' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
