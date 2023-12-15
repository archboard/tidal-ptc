<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Student $resource */
class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'sis_id' => $this->resource->sis_id,
            'student_number' => $this->resource->student_number,
            'name' => $this->resource->name,
            'last_first' => $this->resource->last_first,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'grade_level' => $this->resource->grade_level,
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
