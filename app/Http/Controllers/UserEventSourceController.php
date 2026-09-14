<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;

class UserEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(Request $request, User $user): array
    {
        abort_unless(
            $request->user()?->is($user) || $request->user()?->can(Permission::viewAny, TimeSlot::class),
            403
        );

        return $user->getTimeSlotsFromFullCalendarRequest($request);
    }
}
