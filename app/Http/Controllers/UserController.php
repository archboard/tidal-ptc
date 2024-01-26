<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $title = __('Users');
        $filters = $request->currentFilters();
        $users = $school->users()
            ->filter($filters)
            ->paginate(25);

        return inertia('users/Index', [
            'title' => $title,
            'users' => UserResource::collection($users),
            'availableFilters' => (new User)->availableFiltersToArray(),
            'currentFilters' => (object) $filters,
        ])->withViewData(compact('title'));
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
    public function store(Request $request)
    {
        //
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
