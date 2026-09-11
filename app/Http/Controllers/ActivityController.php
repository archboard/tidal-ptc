<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\TimeSlot;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(Permission::viewAny, TimeSlot::class);

        $filters = $request->validate([
            'event' => ['nullable', 'string'],
            'subject_type' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $activities = Activity::query()
            ->where('school_id', $request->school()->id)
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['subject_type'] ?? null, fn ($query, $type) => $query->where('subject_type', $type))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<', now()->parse($to)->addDay()))
            ->with(['causer', 'subject' => fn ($morph) => $morph->morphWith([TimeSlot::class => ['user']])])
            ->latest('id')
            ->paginate()
            ->withQueryString();

        return inertia('activity/Index', [
            'title' => __('Activity'),
            'activities' => ActivityResource::collection($activities),
            'filters' => $filters,
            'events' => Activity::query()->where('school_id', $request->school()->id)->whereNotNull('event')->distinct()->orderBy('event')->pluck('event'),
            'subjectTypes' => Activity::query()->where('school_id', $request->school()->id)->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('activity.index'))
                    ->isCurrent()
                    ->labeled(__('Activity')),
            ),
        ]);
    }

    /** Recent history for a single time slot, shown in the edit modal. */
    public function timeSlot(TimeSlot $timeSlot): AnonymousResourceCollection
    {
        $this->authorize('viewReservation', $timeSlot);

        $activities = Activity::query()
            ->where('subject_type', $timeSlot->getMorphClass())
            ->where('subject_id', $timeSlot->id)
            ->with('causer')
            ->latest('id')
            ->limit(20)
            ->get();

        return ActivityResource::collection($activities);
    }
}
