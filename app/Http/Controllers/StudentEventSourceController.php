<?php

namespace App\Http\Controllers;

use App\Models\Student;
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
        return $student->getTimeSlotsFromFullCalendarRequest($request);
    }
}
