<?php

namespace App\Http\Resources;

use App\Models\School;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read School $resource */
class SchoolResource extends JsonResource
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
            'timezone' => $this->resource->timezone,
            'allow_online_meetings' => $this->resource->allow_online_meetings,
            'allow_translator_requests' => $this->resource->allow_translator_requests,
            'booking_buffer_hours' => $this->resource->booking_buffer_hours,
            'open_for_contacts_at' => $this->resource->local_open_for_contacts_at?->format('Y-m-d H:i'),
            'close_for_contacts_at' => $this->resource->local_close_for_contacts_at?->format('Y-m-d H:i'),
            'open_for_teachers_at' => $this->resource->local_open_for_teachers_at?->format('Y-m-d H:i'),
            'close_for_teachers_at' => $this->resource->local_close_for_teachers_at?->format('Y-m-d H:i'),
            'contacts_can_book' => $this->resource->contacts_can_book,
            'teachers_can_create' => $this->resource->teachers_can_create,
        ];
    }
}
