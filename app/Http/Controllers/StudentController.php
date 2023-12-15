<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $title = __('Students');
        $students = $school->students()
            ->filter($request->all())
            ->paginate(25);

        return inertia('students/Index', [
            'title' => $title,
            'students' => StudentResource::collection($students),
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
    public function show(Student $student)
    {
        $title = $student->name;
        $student->load('school', 'sections', 'sections.course');

        return inertia('students/Show', [
            'title' => $title,
            'student' => new StudentResource($student),
        ])->withViewData(compact('title'));
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
