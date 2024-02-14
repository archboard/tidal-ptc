<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\SectionResource;
use App\Models\School;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $this->authorize(Permission::viewAny, Section::class);

        $sections = $school->sections()
            ->filter($request->currentFilters())
            ->with('course', 'teacher', 'altTeacher')
            ->withCount('students')
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->orderBy('courses.name')
            ->paginate(25);
        $title = __('Sections');

        return inertia('sections/Index', [
            'title' => $title,
            'sections' => SectionResource::collection($sections),
            'model_alias' => Str::toModelAlias(Section::class),
        ])->withViewData(compact('title'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        $this->authorize(Permission::view, $section);

        $section->load('course', 'teacher', 'altTeacher', 'students');
        $title = __('Section :number of :course_name', [
            'number' => $section->section_number,
            'course_name' => $section->course->name,
        ]);

        return inertia('sections/Show', [
            'title' => $title,
            'section' => new SectionResource($section),
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
