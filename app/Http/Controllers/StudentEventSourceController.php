<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Student $student)
    {
        return $student->getTimeSlotsFromFullCalendarRequest($request);
    }
}
