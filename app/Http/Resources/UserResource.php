<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read User $resource */
class UserResource extends JsonResource
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
            'name' => $this->resource->name,
            'last_first' => $this->resource->last_first,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'timezone' => $this->resource->timezone,
            'school_id' => $this->resource->school_id,
            'can_book' => $this->resource->can_book,
            'is_24h' => $this->resource->is_24h,
            'sections_count' => $this->resource->sections_count,
            'alt_sections_count' => $this->resource->alt_sections_count,
            'time_slots_count' => $this->resource->time_slots_count,
            'user_type' => $this->resource->user_type?->value,
            'user_type_display' => $this->resource->user_type?->label(),
            'schools' => SchoolResource::collection($this->whenLoaded('schools')),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'model_alias' => $this->resource->getMorphClass(),
            'fc_time_format' => $this->resource->full_calendar_format,
            'permissions' => $this->whenLoaded('school', function () {
                return collect($this->resource->school_permissions)
                    ->mapWithKeys(function ($perm) {
                        return [$perm['permission'] => $perm['selected']];
                    });
            }, []),
        ];
    }
}
