<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Student;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class StudentEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(Request $request, Student $student): array
    {
        abort_unless(
            $request->user()?->students()->whereKey($student->id)->exists()
                || $request->user()?->can(Permission::viewAny, TimeSlot::class),
            403
        );

        return $student->getTimeSlotsFromFullCalendarRequest($request);
    }
}
