<?php

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Language $resource */
class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'native_name' => $this->resource->native_name,
            'code' => $this->resource->code,
            'request_max' => $this->resource->pivot?->request_max,
            'overlap_max' => $this->resource->pivot?->overlap_max,
        ];
    }
}
