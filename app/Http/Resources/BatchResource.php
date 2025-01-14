<?php

namespace App\Http\Resources;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Batch $resource */
class BatchResource extends JsonResource
{
    use IsEventSource;

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
            'time_slots_count' => $this->resource->time_slots_count,
            'users_count' => $this->resource->users_count,
            'created_at' => to_local_timezone($this->resource->created_at),
            'time_slots' => TimeSlotResource::collection($this->whenLoaded('timeSlots')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'user' => new UserResource($this->whenLoaded('user')),
            ...$this->getEventSourceAttributes(),
        ];
    }
}
