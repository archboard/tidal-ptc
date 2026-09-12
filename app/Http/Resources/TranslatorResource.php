<?php

namespace App\Http\Resources;

use App\Enums\Language;
use App\Models\Translator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Translator $resource */
class TranslatorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'notes' => $this->resource->notes,
            'active' => $this->resource->active,
            'languages' => $this->resource->languages->map(fn (Language $language) => $language->value)->values(),
            'language_labels' => $this->resource->languages->map(fn (Language $language) => $language->name())->values(),
            'upcoming_count' => $this->whenHas('upcoming_count'),
        ];
    }
}
