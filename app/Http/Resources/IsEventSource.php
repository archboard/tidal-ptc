<?php

namespace App\Http\Resources;

trait IsEventSource
{
    /** @return array<string, mixed> */
    protected function getEventSourceAttributes(): array
    {
        return [
            'event_source_id' => $this->resource->fullCalendarEventSourceId(),
            'event_source' => $this->resource->fullCalendarEventSource(),
        ];
    }
}
