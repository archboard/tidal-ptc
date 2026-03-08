<?php

namespace App\Http\Resources;

use App\Models\SchoolLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read SchoolLanguage $resource
 * @mixin SchoolLanguage
 */
class SchoolLanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language' => new LanguageResource($this->language),
            'overlap_max' => $this->overlap_max,
            'request_max' => $this->request_max,
        ];
    }
}
