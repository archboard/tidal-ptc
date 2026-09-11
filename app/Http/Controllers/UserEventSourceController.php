<?php

namespace App\Http\Controllers;

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
        return $user->getTimeSlotsFromFullCalendarRequest($request);
    }
}
