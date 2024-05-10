<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\School;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class SchoolEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, School $school)
    {
        BouncerFacade::scope()->onceTo($school->id, fn () => $this->authorize(Permission::viewAny, TimeSlot::class));

        return $school->getTimeSlotsFromFullCalendarRequest($request);
    }
}
