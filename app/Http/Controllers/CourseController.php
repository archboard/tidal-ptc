<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\School;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $title = __('Courses');
        $courses = $school->courses()
            ->filter($request->all())
            ->withCount('sections')
            ->paginate(25);

        return inertia('courses/Index', [
            'title' => $title,
            'courses' => CourseResource::collection($courses),
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
