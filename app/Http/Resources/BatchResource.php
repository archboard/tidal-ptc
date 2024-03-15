<?php

namespace App\Http\Resources;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Batch $resource */
class BatchResource extends JsonResource
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
            'user_id' => $this->resource->user_id,
            'event_source' => $this->resource->fullCalendarEventSource(),
            'time_slots' => TimeSlotResource::collection($this->whenLoaded('timeSlots')),
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
