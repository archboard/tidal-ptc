<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\CreateTimeSlotRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\School;
use App\Models\TimeSlot;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use stdClass;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize(Permission::viewAny, TimeSlot::class);

        $batches = Batch::query()
            ->orderBy('created_at', 'desc')
            ->withCount('timeSlots', 'users')
            ->with('user')
            ->paginate();

        return inertia('time-slots/batches/Index', [
            'title' => __('Time Slot Batches'),
            'batches' => BatchResource::collection($batches),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->isCurrent()
                    ->labeled(__('Time slot batches'))
                    ->to(route('batches.index'))
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize(Permission::create, TimeSlot::class);

        session()->flash('error', __('Select teachers to create time slots.'));

        return to_route('teachers.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, School $school)
    {
        $this->authorize(Permission::create, TimeSlot::class);
        $user = $request->user();

        // Clean up empty time slots
        Batch::query()
            ->where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->whereDoesntHave('timeSlots')
            ->delete();

        $batch = $user->createBatchFromSelection();

        return to_route('batches.edit', $batch);
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
    public function edit(School $school, Batch $batch)
    {
        $this->authorize(Permission::update, TimeSlot::class);

        $school->load('languages');
        $batch->load('users');

        return inertia('time-slots/batches/Edit', [
            'title' => __('Edit time slot batch'),
            'batch' => new BatchResource($batch),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Time slot batches'))
                    ->to(route('batches.index')),
                NavigationItem::make()
                    ->to(route('batches.edit', $batch))
                    ->labeled(__('Edit batch'))
                    ->isCurrent()
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Authorization handled in the Request class.
     */
    public function update(CreateTimeSlotRequest $request, Batch $batch)
    {
        $batch->updateTimeSlots($request->getTimeSlotAttributes(false));

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot updated successfully.'),
            'data' => new stdClass,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
