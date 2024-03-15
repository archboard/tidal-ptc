<?php

namespace App\Http\Resources;

use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read TimeSlot $resource */
class TimeSlotResource extends JsonResource
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
            'batch_id' => $this->resource->batch_id,
            'starts_at' => $this->resource->starts_at?->toDateTimeString(),
            'ends_at' => $this->resource->ends_at?->toDateTimeString(),
            'reserved_at' => $this->resource->reserved_at?->toDateTimeString(),
            'teacher_notes' => $this->resource->teacher_notes,
            'contact_notes' => $this->resource->contact_notes,
            'translator_notes' => $this->resource->translator_notes,
            'location' => $this->resource->location,
            'meeting_url' => $this->resource->meeting_url,
            'allow_online_meetings' => $this->resource->allow_online_meetings,
            'is_online' => $this->resource->is_online,
            'requested_online' => $this->resource->requested_online,
            'contact_can_book' => $this->resource->contact_can_book,
            'allow_translator_requests' => $this->resource->allow_translator_requests,
        ];
    }
}
