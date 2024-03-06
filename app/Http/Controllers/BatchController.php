<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\School;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, School $school)
    {
        $this->authorize(Permission::create, TimeSlot::class);

        $batch = Batch::create([
            'tenant_id' => $school->tenant_id,
            'school_id' => $school->id,
            'user_id' => $request->user()->id,
        ]);

        return new BatchResource($batch);
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
