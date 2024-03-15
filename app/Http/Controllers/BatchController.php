<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\CreateOrUpdateTimeSlotRequest;
use App\Http\Requests\EditBatchTimeSlotRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, School $school)
    {
        $this->authorize(Permission::create, TimeSlot::class);
        $user = $request->user();

        Batch::query()
            ->where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->whereDoesntHave('timeSlots')
            ->delete();

        $batch = Batch::create([
            'tenant_id' => $school->tenant_id,
            'school_id' => $school->id,
            'user_id' => $request->user()->id,
        ]);

        $batchUsers = $user->getModelSelection(User::class)
            ->map(fn ($id) => [
                'batch_id' => $batch->id,
                'user_id' => $id,
            ]);

        DB::table('batch_users')->insert($batchUsers->toArray());

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
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Authorization handled in the Request class.
     */
    public function update(CreateOrUpdateTimeSlotRequest $request, Batch $batch)
    {
        $batch->updateTimeSlots($request->getTimeSlotAttributes(false));

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot updated successfully.'),
            'data' => new stdClass(),
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
