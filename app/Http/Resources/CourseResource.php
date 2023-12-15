<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Course $resource */
class CourseResource extends JsonResource
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
            'sis_id' => $this->resource->sis_id,
            'course_number' => $this->resource->course_number,
            'name' => $this->resource->name,
            'sections_count' => $this->resource->sections_count,
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
