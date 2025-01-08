<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'model_alias' => Str::toModelAlias(User::class),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Users'))
                    ->to(route('users.index'))
                    ->isCurrent()
            ),
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
    public function show(User $user)
    {
        $title = $user->name;

        $user->load('students');

        return inertia('users/Show', [
            'title' => $title,
            'canOwnTimeSlots' => $user->canOwnTimeSlots(),
            'subjectUser' => new UserResource($user),
        ])->withViewData(compact('title'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user)
    {
        $this->authorize(Permission::create, TimeSlot::class);

        $request->school()->load('languages');

        return inertia('time-slots/Create', [
            'title' => __('Edit times slots for :name', ['name' => $user->name]),
            'events' => $user->fullCalendarEventUrl(),
            'userSubject' => new UserResource($user),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('users.index'))
                    ->labeled(__('Users')),
                NavigationItem::make()
                    ->to(route('users.show', $user))
                    ->labeled($user->name),
                NavigationItem::make()
                    ->to(route('users.show', $user))
                    ->isCurrent()
                    ->labeled(__('Edit time slots')),
            ),
        ]);
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
