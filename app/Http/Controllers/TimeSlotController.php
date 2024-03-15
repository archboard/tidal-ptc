<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\CreateOrUpdateTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
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
        $this->authorize(Permission::create, TimeSlot::class);
        $request->school()->load('languages');

        return inertia('time-slots/Create', [
            'title' => __('Create time slots'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOrUpdateTimeSlotRequest $request)
    {
        $user = $request->user();
        $attributes = $request->getTimeSlotAttributes();

        // Create for selection when batch is set
        TimeSlot::createForSelection($user->getModelSelection(User::class), $attributes);

        // Create for individual user

        return response()->json([
            'level' => 'success',
            'message' => __('Time slot created successfully.'),
            'data' => TimeSlot::make($attributes)->toFullCalendar(),
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
    public function destroy(string $id)
    {
        //
    }
}
