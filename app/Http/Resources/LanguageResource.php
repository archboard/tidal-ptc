<?php

namespace App\Http\Resources;

use App\Enums\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Language $resource
 *
 * @mixin Language
 */
class LanguageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->value,
            'code' => $this->value,
            'name' => $this->name(),
            'native_name' => $this->nativeName(),
        ];
    }
}
