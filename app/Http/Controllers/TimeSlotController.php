<?php

namespace App\Http\Controllers;

use App\Enums\NotificationEvent;
use App\Enums\Permission;
use App\Http\Requests\CreateTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\UserResource;
use App\Models\Batch;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use App\Navigation\NavigationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TimeSlotController extends Controller
{
    /** Changes to a reserved slot that the contact should hear about. */
    protected const array NOTIFIABLE_CHANGES = ['starts_at', 'ends_at', 'location', 'meeting_url', 'is_online', 'teacher_notes'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school): Response
    {
        /** @var User $user */
        $user = $request->user();

        return inertia('time-slots/Index', [
            'title' => __('Time slots'),
            'eventSources' => $user->getFullCalendarEventSources(),
            'canCreateTimeSlots' => $user->can('createOrForSelf', TimeSlot::class),
            'canViewBatches' => $user->can(Permission::viewAny, TimeSlot::class),
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
    public function create(Request $request): Response
    {
        $this->authorize('createOrForSelf', TimeSlot::class);

        $request->school()->load('languages');
        /** @var User $user */
        $user = $request->user();

        return inertia('time-slots/Manage', [
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
    public function store(CreateTimeSlotRequest $request): JsonResponse
    {
        $this->authorize('createOrForSelf', TimeSlot::class);

        /** @var User $user */
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
            $timeSlot = new TimeSlot($attributes);
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
     * Update the specified resource in storage.
     */
    public function update(UpdateTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse
    {
        $data = $request->validated();

        abort_if(
            $timeSlot->isReserved()
                && ! $request->user()?->can(Permission::update, $timeSlot)
                && (! $timeSlot->starts_at->equalTo($data['starts_at']) || ! $timeSlot->ends_at->equalTo($data['ends_at'])),
            403,
            __('Reserved time slots cannot be moved.')
        );

        if ($request->updateBatch()) {
            $data['starts_at'] = $timeSlot->starts_at->toDateTimeString();
            $data['ends_at'] = $timeSlot->ends_at->toDateTimeString();
            /** @var Batch $batch */
            $batch = Batch::findOrFail($data['batch_id']);
            $affected = $batch->timeSlots()
                ->where('starts_at', $data['starts_at'])
                ->where('ends_at', $data['ends_at'])
                ->reserved()
                ->get()
                ->filter(fn (TimeSlot $slot) => $slot->fill($data)->isDirty(self::NOTIFIABLE_CHANGES));
            $batch->updateTimeSlots($data);
        } else {
            $affected = collect([$timeSlot->fill($data)])
                ->filter(fn (TimeSlot $slot) => $slot->isReserved() && $slot->isDirty(self::NOTIFIABLE_CHANGES));
            $timeSlot->save();
        }

        $affected->each(function (TimeSlot $slot) {
            $slot->refresh()->notifyReservation(NotificationEvent::slot_updated);
            $slot->update(['contact_reminded_at' => null, 'staff_reminded_at' => null]);
        });

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot updated successfully.'),
            'data' => $timeSlot->toFullCalendar(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $this->authorize('deleteOrForSelf', $timeSlot);

        if ($timeSlot->isReserved()) {
            abort_unless((bool) $request->user()?->can(Permission::update, $timeSlot), 403, __('Reserved time slots cannot be deleted.'));
            $timeSlot->notifyReservation(NotificationEvent::slot_cancelled);
        }

        $timeSlot->delete();

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot deleted successfully.'),
        ]);
    }
}
