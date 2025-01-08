<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\CreateTimeSlotRequest;
use App\Http\Resources\UserResource;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $user = $request->user();

        return inertia('time-slots/Index', [
            'title' => __('Time slots'),
            'eventSources' => $request->user()->getFullCalendarEventSources(),
            'canCreateTimeSlots' => $user->can('createOrForSelf', TimeSlot::class),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('time-slots.index'))
                    ->isCurrent()
                    ->labeled(__('Time slots')),
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('createOrForSelf', TimeSlot::class);

        $request->school()->load('languages');
        $user = $request->user();

        return inertia('time-slots/Create', [
            'title' => __('Manage my time slots'),
            'events' => $user->fullCalendarEventUrl(),
            'userSubject' => new UserResource($user),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('time-slots.index'))
                    ->labeled(__('Time slots')),
                NavigationItem::make()
                    ->to(route('time-slots.create'))
                    ->isCurrent()
                    ->labeled(__('Manage')),
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTimeSlotRequest $request)
    {
        $this->authorize('createOrForSelf', TimeSlot::class);

        $user = $request->user();
        $attributes = $request->getTimeSlotAttributes();

        // Create for selection when batch is set
        if (isset($attributes['batch_id'])) {
            $selection = $user->getModelSelection(User::class, function ($query) {
                $query->whereHasMorph('selectable', User::class, function ($query) {
                    $query->where('can_book', true);
                });
            });

            TimeSlot::createForSelection($selection, $attributes);
            $timeSlot = TimeSlot::make($attributes);
        } else {
            $timeSlot = TimeSlot::create($attributes);
        }

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot created successfully.'),
            'data' => $timeSlot->toFullCalendar(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeSlot $timeSlot)
    {
        $this->authorize('deleteOrForSelf', $timeSlot);

        $timeSlot->delete();

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot deleted successfully.'),
        ]);
    }
}
