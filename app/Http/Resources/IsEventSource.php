<?php

namespace App\Http\Resources;

trait IsEventSource
{
    protected function getEventSourceAttributes(): array
    {
        return [
            'event_source_id' => $this->resource->fullCalendarEventSourceId(),
            'event_source' => $this->resource->fullCalendarEventSource(),
        ];
    }
}