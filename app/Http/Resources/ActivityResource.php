<?php

namespace App\Http\Resources;

use App\Enums\ActivityEvent;
use App\Enums\NotificationEvent;
use App\Models\Activity;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Activity $resource */
class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'event' => $this->resource->event,
            'description' => __($this->resource->description, $this->translationReplacements()),
            'causer' => $this->resource->causer?->getAttribute('name'),
            'subject_type' => $this->resource->subject_type,
            'subject_id' => $this->resource->subject_id,
            'subject' => $this->subjectLabel($this->resource->subject),
            'changes' => $this->resource->attribute_changes?->toArray(),
            'properties' => $this->resource->properties?->toArray(),
            'created_at' => to_local_timezone($this->resource->created_at),
        ];
    }

    /**
     * Scalar properties become placeholder replacements; labels that must be translated
     * (like the notification name) are resolved here rather than stored.
     *
     * @return array<string, string>
     */
    protected function translationReplacements(): array
    {
        $properties = collect($this->resource->properties?->toArray() ?? [])
            ->filter(fn ($value) => is_scalar($value))
            ->map(fn ($value) => (string) $value);

        if ($this->resource->event === ActivityEvent::notification_sent->value) {
            $properties->put('notification', NotificationEvent::tryFrom($properties->get('event', ''))?->name() ?? '');
        }

        return $properties->all();
    }

    protected function subjectLabel(?Model $subject): ?string
    {
        return match (true) {
            $subject === null => null,
            $subject instanceof TimeSlot => to_local_timezone($subject->starts_at).' · '.$subject->user->name,
            default => $subject->getAttribute('name') ?? class_basename($subject).' #'.$subject->getKey(),
        };
    }
}
